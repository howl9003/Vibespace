/*
 * as-cgi.c -- CGI replacement for the mod_as Apache module.
 *
 * Invoked by nginx/fcgiwrap for *.as requests.  Opens a TCP connection to
 * the Archspace game server, speaks the binary message protocol defined in
 * as.h / net.h / message.cc, and writes the resulting HTML page to stdout
 * as a proper CGI response.
 *
 * WIRE FORMAT REFERENCE
 * =====================
 * Every packet on the wire is laid out exactly as SPacket in as.h:
 *
 *   Byte 0      : total_packet_size % 256          (size low byte)
 *   Byte 1      : total_packet_size / 256          (size high byte)
 *   Bytes 2-3   : message type (unsigned 16-bit LE) -- SMessageHeader.type
 *   Bytes 4-5   : server ID   (unsigned 16-bit LE) -- SMessageHeader.server
 *   Bytes 6-7   : counter     (unsigned 16-bit LE) -- SMessageHeader.counter
 *   Bytes 8..N  : data items
 *
 * MESSAGE_HEADER_SIZE = 8.  The "size" field encodes the TOTAL wire length
 * (header + data).
 *
 * Cross-reference: net.h make_packet() lines 244-246:
 *   header_byte[0] = MESSAGE_HEADER_SIZE % 256;
 *   header_byte[1] = MESSAGE_HEADER_SIZE / 256;
 * and message.cc CMessage::set_packet() lines 44-46 (identical layout).
 *
 * DATA ITEMS (TLV encoding, from net.h set_item_to_packet / message.cc set_item)
 * -------------------------------------------------------------------------------
 * Each item:
 *   Byte 0      : item header byte
 *                   bits 7-2: item type (6 bits)
 *                   bits 1-0: bytes-of-length (1 or 2)
 *   Byte(s) 1-2 : length field (1 byte if < 256, 2 bytes LE if < 4000)
 *   Bytes ...   : data payload (omitted for MESSAGE_ITEM_LIST)
 *
 * ItemHeader = ((type & 0x3F) << 2) | ByteOfLength
 * (net.h line 289, message.cc line 250)
 *
 * Item types used here:
 *   MESSAGE_ITEM_UINT1 (011 octal = 0x09) -- block counter byte
 *   MESSAGE_ITEM_ASCII (001 octal = 0x01) -- string payload
 *
 * STRING PACKETS ("make_string_packet" in net.h, "send_string_packet" in connection.cc)
 * --------------------------------------------------------------------------------------
 * Strings longer than STRING_DATA_BLOCK (3980) are split across multiple
 * packets of the same type.  Each packet carries:
 *   item 1: UINT1  = block sequence counter (starts at 0, increments)
 *   item 2: ASCII  = up to STRING_DATA_BLOCK bytes of the string
 *
 * The game reassembles by appending each block's ASCII payload in order
 * (connection.cc get_uri / get_cookie etc. all call mXxx.append()).
 *
 * SEND SEQUENCE (mod_as.c archspace_handler lines 640-685)
 * ---------------------------------------------------------
 *   MT_URL_SEND(0x8001)              -- request URI / path
 *   MT_REFERER_SEND(0x8005)          -- HTTP_REFERER (optional)
 *   MT_METHOD_SEND(0x8003)           -- REQUEST_METHOD
 *   MT_COOKIE_SEND(0x8007)           -- HTTP_COOKIE (optional)
 *   MT_ACCEPT_ENCODING_SEND(0x8009)  -- HTTP_ACCEPT_ENCODING (optional)
 *   MT_ACCEPT_LANGUAGE_SEND(0x800B)  -- HTTP_ACCEPT_LANGUAGE (optional)
 *   MT_USER_AGENT_SEND(0x800D)       -- HTTP_USER_AGENT (optional)
 *   MT_HOST_NAME_SEND(0x800F)        -- HTTP_HOST (optional)
 *   MT_CONNECTION_INFO_SEND(0x8011)  -- REMOTE_ADDR (optional)
 *   MT_QUERY_SEND(0x8013)            -- QUERY_STRING or POST body (optional)
 *   MT_GET_PAGE_REQUEST(0x8015)      -- no data, triggers page generation
 *
 * RECEIVE SEQUENCE (net.h receive_packet_from_gameserver)
 * -------------------------------------------------------
 *   MT_HEADER_SEND(0x8101)     -- extra HTTP headers (not used in mod_as output)
 *   MT_SET_COOKIE_SEND(0x8103) -- Set-Cookie value(s), string-chunked
 *   MT_CONTENT_SEND(0x8105)    -- HTML body, string-chunked
 *   MT_TERMINATE_REQUEST(0x0000) -- signals end of response
 *
 * Server ID field
 * ---------------
 * mod_as computes: ServerID = 0x0400 + Config->server_serial  (net.h line 231)
 * We default server_serial=0 so ServerID=0x0400 unless ARCHSPACE_SERVER_ID
 * is set in the environment.
 */

/* inet_aton / gethostbyname need _BSD_SOURCE or _GNU_SOURCE on glibc */
#define _GNU_SOURCE

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <errno.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <netdb.h>

/* -------------------------------------------------------------------------
 * Protocol constants (from as.h)
 * ---------------------------------------------------------------------- */
#define MT_TERMINATE_REQUEST        0x0000
#define MT_ERROR_SEND               0x0001
#define MT_URL_SEND                 0x8001
#define MT_METHOD_SEND              0x8003
#define MT_REFERER_SEND             0x8005
#define MT_COOKIE_SEND              0x8007
#define MT_ACCEPT_ENCODING_SEND     0x8009
#define MT_ACCEPT_LANGUAGE_SEND     0x800B
#define MT_USER_AGENT_SEND          0x800D
#define MT_HOST_NAME_SEND           0x800F
#define MT_CONNECTION_INFO_SEND     0x8011
#define MT_QUERY_SEND               0x8013
#define MT_GET_PAGE_REQUEST         0x8015
#define MT_HEADER_SEND              0x8101
#define MT_SET_COOKIE_SEND          0x8103
#define MT_CONTENT_SEND             0x8105

#define MAX_PACKET_SIZE             4096
#define MESSAGE_HEADER_SIZE         8
#define MAX_MESSAGE_DATA_SIZE       4000

/* Maximum string chunk per packet (net.h STRING_DATA_BLOCK = 3980) */
#define STRING_DATA_BLOCK           3980

/* Item type codes (from as.h, octal) */
#define MESSAGE_ITEM_ASCII          0x01   /* 001 octal */
#define MESSAGE_ITEM_UINT1          0x09   /* 011 octal */

/* Default game server coordinates */
#define DEFAULT_GAME_HOST           "127.0.0.1"
#define DEFAULT_GAME_PORT           12350

/* -------------------------------------------------------------------------
 * Packet structure
 *
 * Layout on the wire (mirroring SPacket / net.h):
 *   bytes[0]   = total_size % 256
 *   bytes[1]   = total_size / 256
 *   bytes[2-3] = type   (LE uint16)
 *   bytes[4-5] = server (LE uint16)
 *   bytes[6-7] = counter(LE uint16)
 *   bytes[8..] = data items
 *
 * We store the packet as a raw byte buffer of MAX_PACKET_SIZE bytes so we
 * can hand its address directly to write(2).
 * ---------------------------------------------------------------------- */
typedef struct {
    unsigned char buf[MAX_PACKET_SIZE];
    int           size;   /* total bytes currently in buf */
} Packet;

/* Global monotonic counter -- mirrors net.h static Counter (line 20) */
static unsigned short g_counter = 0;

/* Server ID: 0x0400 + serial (net.h make_packet line 231) */
static unsigned short g_server_id = 0x0400;

/* -------------------------------------------------------------------------
 * Low-level helpers
 * ---------------------------------------------------------------------- */

/*
 * Write exactly n bytes to fd, retrying on short writes.
 * Returns 0 on success, -1 on error.
 */
static int write_all(int fd, const void *buf, int n)
{
    const unsigned char *p = (const unsigned char *)buf;
    int remaining = n;
    while (remaining > 0) {
        int done = (int)write(fd, p, (size_t)remaining);
        if (done < 0) {
            if (errno == EINTR) continue;
            return -1;
        }
        p         += done;
        remaining -= done;
    }
    return 0;
}

/*
 * Read exactly n bytes from fd, retrying on partial reads.
 * Returns 0 on success, -1 on error/EOF.
 */
static int read_all(int fd, void *buf, int n)
{
    unsigned char *p = (unsigned char *)buf;
    int remaining = n;
    while (remaining > 0) {
        int done = (int)read(fd, p, (size_t)remaining);
        if (done < 0) {
            if (errno == EINTR) continue;
            return -1;
        }
        if (done == 0) return -1; /* EOF */
        p         += done;
        remaining -= done;
    }
    return 0;
}

/* -------------------------------------------------------------------------
 * Packet construction helpers
 * ---------------------------------------------------------------------- */

/*
 * Initialise a Packet as a bare header for 'msg_type'.
 * Mirrors net.h make_packet(): sets size=8, encodes size into bytes[0-1],
 * writes type/server/counter into bytes[2-7] as LE uint16 pairs.
 *
 * Wire layout after init:
 *   buf[0] = 8 % 256 = 8
 *   buf[1] = 8 / 256 = 0
 *   buf[2] = type & 0xFF
 *   buf[3] = type >> 8
 *   buf[4] = server_id & 0xFF
 *   buf[5] = server_id >> 8
 *   buf[6] = counter & 0xFF
 *   buf[7] = counter >> 8
 */
static void pkt_init(Packet *p, int msg_type)
{
    memset(p->buf, 0, sizeof(p->buf));
    p->size = MESSAGE_HEADER_SIZE;

    /* bytes[0-1]: total size (LE) -- net.h lines 245-246 */
    p->buf[0] = (unsigned char)(MESSAGE_HEADER_SIZE % 256);
    p->buf[1] = (unsigned char)(MESSAGE_HEADER_SIZE / 256);

    /* bytes[2-3]: message type (LE) */
    p->buf[2] = (unsigned char)(msg_type & 0xFF);
    p->buf[3] = (unsigned char)((msg_type >> 8) & 0xFF);

    /* bytes[4-5]: server ID (LE) */
    p->buf[4] = (unsigned char)(g_server_id & 0xFF);
    p->buf[5] = (unsigned char)((g_server_id >> 8) & 0xFF);

    /* bytes[6-7]: counter (LE) */
    p->buf[6] = (unsigned char)(g_counter & 0xFF);
    p->buf[7] = (unsigned char)((g_counter >> 8) & 0xFF);
    g_counter++;
}

/*
 * Append one TLV item to a Packet.
 * Mirrors net.h set_item_to_packet() and message.cc CMessage::set_item().
 *
 * item_type : MESSAGE_ITEM_UINT1 or MESSAGE_ITEM_ASCII
 * data      : payload bytes
 * data_size : payload length in bytes
 *
 * ItemHeader byte = ((item_type & 0x3F) << 2) | ByteOfLength
 *   where ByteOfLength = 1 if data_size < 256, else 2.
 *
 * Returns 0 on success, -1 if item would overflow MAX_MESSAGE_DATA_SIZE.
 */
static int pkt_add_item(Packet *p, int item_type,
                        const void *data, int data_size)
{
    unsigned char item_hdr;
    unsigned char sz_bytes[2];
    int           byte_of_len;
    int           needed;

    if (data_size < 0) return -1;

    if (data_size < 256) {
        byte_of_len  = 1;
        sz_bytes[0]  = (unsigned char)data_size;
    } else if (data_size < MAX_MESSAGE_DATA_SIZE) {
        byte_of_len  = 2;
        sz_bytes[0]  = (unsigned char)(data_size % 256);
        sz_bytes[1]  = (unsigned char)(data_size / 256);
    } else {
        return -1;
    }

    /* total bytes this item adds to the data region */
    needed = 1 /* item_hdr */ + byte_of_len + data_size;

    /* guard: data region must fit in MAX_MESSAGE_DATA_SIZE
     * (p->size includes the 8-byte header, so data used = p->size - 8) */
    if ((p->size - MESSAGE_HEADER_SIZE) + needed > MAX_MESSAGE_DATA_SIZE)
        return -1;

    item_hdr = (unsigned char)(((item_type & 0x3F) << 2) | byte_of_len);

    /* Write item header */
    p->buf[p->size] = item_hdr;
    p->size++;

    /* Write length field */
    p->buf[p->size] = sz_bytes[0];
    p->size++;
    if (byte_of_len == 2) {
        p->buf[p->size] = sz_bytes[1];
        p->size++;
    }

    /* Write data payload */
    if (data_size > 0 && data != NULL)
        memcpy(p->buf + p->size, data, (size_t)data_size);
    p->size += data_size;

    /* Update the size field in the header (bytes[0-1]) */
    p->buf[0] = (unsigned char)(p->size % 256);
    p->buf[1] = (unsigned char)(p->size / 256);

    return 0;
}

/*
 * Send a Packet to the game server socket, handling partial writes.
 * Mirrors net.h send_packet() (blocking version, no EAGAIN loop needed
 * since we keep the socket in blocking mode unlike the original).
 */
static int pkt_send(int sock, Packet *p)
{
    return write_all(sock, p->buf, p->size);
}

/* -------------------------------------------------------------------------
 * Receive a single packet from the game server.
 *
 * Mirrors net.h receive_packet():
 *   1. Read first 2 bytes to get total size (LE).
 *   2. Read remaining (size - 2) bytes.
 *   3. Parse type from bytes[2-3].
 *
 * Caller must supply a Packet buffer to fill.
 * Returns 0 on success, -1 on error.
 * ---------------------------------------------------------------------- */
static int pkt_recv(int sock, Packet *p)
{
    unsigned char size_buf[2];
    unsigned short total_size;
    int remaining;

    /* Step 1: read the 2-byte size prefix */
    if (read_all(sock, size_buf, 2) < 0)
        return -1;

    /* LE decode: net.h receive_packet line 176 */
    total_size = (unsigned short)((int)size_buf[0] + (int)size_buf[1] * 256);

    if (total_size < MESSAGE_HEADER_SIZE || total_size > MAX_PACKET_SIZE)
        return -1;

    /* Copy size bytes into buf */
    p->buf[0] = size_buf[0];
    p->buf[1] = size_buf[1];

    /* Step 2: read the rest of the packet */
    remaining = (int)total_size - 2;
    if (remaining > 0) {
        if (read_all(sock, p->buf + 2, remaining) < 0)
            return -1;
    }

    p->size = (int)total_size;
    return 0;
}

/*
 * Extract the message type (LE uint16 at bytes[2-3]) from a received Packet.
 */
static unsigned short pkt_type(const Packet *p)
{
    return (unsigned short)((int)p->buf[2] | ((int)p->buf[3] << 8));
}

/* -------------------------------------------------------------------------
 * String-packet sender
 *
 * Replicates net.h make_string_packet() / connection.cc send_string_packet().
 * Splits 'str' into STRING_DATA_BLOCK-byte chunks, each sent as a separate
 * packet with two items:
 *   item 1: UINT1   = block index (0, 1, 2, ...)
 *   item 2: ASCII   = chunk of the string
 *
 * Returns 0 on success, -1 on error.
 * ---------------------------------------------------------------------- */
static int send_string(int sock, int msg_type, const char *str)
{
    int           total_len;
    int           done = 0;
    unsigned char block_idx = 0;

    if (!str) return 0;
    total_len = (int)strlen(str);
    if (total_len == 0) return 0;

    while (done < total_len) {
        Packet pkt;
        int    chunk_size;

        chunk_size = total_len - done;
        if (chunk_size > STRING_DATA_BLOCK)
            chunk_size = STRING_DATA_BLOCK;

        pkt_init(&pkt, msg_type);

        /* Item 1: block counter (UINT1) -- net.h lines 409-411 */
        if (pkt_add_item(&pkt, MESSAGE_ITEM_UINT1,
                         &block_idx, (int)sizeof(unsigned char)) < 0)
            return -1;

        /* Item 2: ASCII string chunk -- net.h lines 412-414 */
        if (pkt_add_item(&pkt, MESSAGE_ITEM_ASCII,
                         str + done, chunk_size) < 0)
            return -1;

        if (pkt_send(sock, &pkt) < 0)
            return -1;

        done += chunk_size;
        block_idx++;
    }
    return 0;
}

/*
 * Send a bare packet with no data items (used for MT_GET_PAGE_REQUEST).
 * Mirrors net.h make_getpage_request / make_packet: just the 8-byte header.
 */
static int send_bare(int sock, int msg_type)
{
    Packet pkt;
    pkt_init(&pkt, msg_type);
    return pkt_send(sock, &pkt);
}

/* -------------------------------------------------------------------------
 * TCP connection to the game server
 *
 * Mirrors net.h make_connection() but uses blocking I/O (no nonblock call)
 * so we can use simple read_all/write_all loops.
 * ---------------------------------------------------------------------- */
static int game_connect(const char *host, int port)
{
    int                 sock;
    struct sockaddr_in  addr;
    struct hostent     *he;

    memset(&addr, 0, sizeof(addr));
    addr.sin_family = AF_INET;
    addr.sin_port   = htons((unsigned short)port);

    /* Try numeric first, fall back to DNS (net.h make_connection lines 74-82) */
    if (inet_aton(host, &addr.sin_addr) == 0) {
        he = gethostbyname(*host ? host : "localhost");
        if (!he) return -1;
        memcpy(&addr.sin_addr, he->h_addr_list[0], (size_t)he->h_length);
    }

    sock = socket(AF_INET, SOCK_STREAM, 0);
    if (sock < 0) return -1;

    if (connect(sock, (struct sockaddr *)&addr, sizeof(addr)) < 0) {
        close(sock);
        return -1;
    }
    return sock;
}

/* -------------------------------------------------------------------------
 * CGI error page output
 *
 * On connection failure mirrors mod_as.c error_message() and uses the same
 * HTML boilerplate so the player sees the familiar "game server down" page.
 * ---------------------------------------------------------------------- */
static void cgi_error(const char *msg)
{
    printf("Content-Type: text/html; charset=utf-8\r\n");
    printf("Pragma: no-cache\r\n");
    printf("Cache-Control: no-cache\r\n");
    printf("Expires: 0\r\n");
    printf("\r\n");
    printf("<html>\n"
           "<head>\n"
           "<title>*********ARCHSPACE************</title>\n"
           "<meta http-equiv=\"Content-Type\" content=\"text/html;"
           " charset=utf-8\">\n"
           /* When a .as request errors inside the game frameset, this page
              lands in the main frame and sits next to a now-stale/broken left
              menu frame. Break out so the whole window shows one clean message.
              location.replace avoids a history entry; once we are the top
              document the guard is false, so there is no redirect loop. */
           "<script type=\"text/javascript\">\n"
           "if (window.top !== window.self) {"
           " window.top.location.replace(window.self.location.href); }\n"
           "</script>\n"
           "</head>\n"
           "<body bgcolor=\"#000000\" style=\"margin:0;\">\n"
           "<div style=\"max-width:620px;margin:16%% auto 0;padding:0 24px;"
           "text-align:center;color:#999999;font-family:sans-serif;"
           "font-size:15px;line-height:1.6;\">%s</div>\n"
           "</body>\n"
           "</html>\n", msg);
}

/* -------------------------------------------------------------------------
 * Extract the ASCII string payload from a received string-chunk packet.
 *
 * A string chunk packet has the data layout (net.h set_item_to_packet,
 * as accessed by net.h set_content / set_cookie):
 *
 *   data[0]           : item header for UINT1 (block counter)
 *   data[1]           : 1 (length=1 for a single byte)
 *   data[2]           : block index value
 *   data[3]           : item header for ASCII
 *   data[4] or [4-5]  : length of ASCII payload (1 or 2 bytes LE)
 *   data[5 or 6] ...  : ASCII payload
 *
 * This mirrors the direct data[] indexing in net.h set_cookie (lines 898-929)
 * and set_content (lines 988-1003):
 *   ByteOfSize = data[3] & 0x03
 *   if ByteOfSize == 2: size = data[4] + data[5]*256, payload at data[6]
 *   if ByteOfSize == 1: size = data[4],               payload at data[5]
 *
 * Writes up to 'buf_size' bytes into 'buf', NUL-terminates, returns length.
 * Returns -1 if the packet cannot be parsed.
 * ---------------------------------------------------------------------- */
static int extract_string_chunk(const Packet *p,
                                char *buf, int buf_size)
{
    const unsigned char *d;
    int                  data_len;
    int                  byte_of_size;
    int                  payload_size;
    int                  payload_off;
    int                  copy_len;

    /* Data region starts at offset MESSAGE_HEADER_SIZE */
    d        = p->buf + MESSAGE_HEADER_SIZE;
    data_len = p->size - MESSAGE_HEADER_SIZE;

    /*
     * Minimum layout:
     *   [0] UINT1 item hdr
     *   [1] length=1
     *   [2] block index
     *   [3] ASCII item hdr
     *   [4] length byte(s)
     */
    if (data_len < 5) return -1;

    /* ASCII item header is at d[3]; extract ByteOfSize from its low 2 bits */
    byte_of_size = (int)(d[3] & 0x03);

    if (byte_of_size == 1) {
        payload_size = (int)d[4];
        payload_off  = 5;
    } else if (byte_of_size == 2) {
        if (data_len < 6) return -1;
        payload_size = (int)d[4] + (int)d[5] * 256;
        payload_off  = 6;
    } else {
        return -1;
    }

    if (payload_off + payload_size > data_len) return -1;

    copy_len = payload_size;
    if (copy_len > buf_size - 1) copy_len = buf_size - 1;
    memcpy(buf, d + payload_off, (size_t)copy_len);
    buf[copy_len] = '\0';

    return copy_len;
}

/* -------------------------------------------------------------------------
 * Accumulate response from the game server.
 *
 * We collect all MT_CONTENT_SEND chunks into a dynamically grown buffer and
 * all MT_SET_COOKIE_SEND chunks into a cookie buffer (the game sends the
 * full Set-Cookie value as a string).
 *
 * MT_HEADER_SEND packets are noted but not forwarded (mod_as.c line 717
 * has set_header() commented out).
 *
 * Returns 0 on success, -1 on protocol error.
 * ---------------------------------------------------------------------- */
#define CONTENT_INITIAL  65536
#define CONTENT_GROW     65536

typedef struct {
    char  *data;
    int    len;
    int    cap;
} Buffer;

static int buf_append(Buffer *b, const char *src, int n)
{
    if (n <= 0) return 0;
    if (b->len + n >= b->cap) {
        int   new_cap = b->cap + CONTENT_GROW;
        char *new_data;
        while (new_cap <= b->len + n) new_cap += CONTENT_GROW;
        new_data = (char *)realloc(b->data, (size_t)new_cap);
        if (!new_data) return -1;
        b->data = new_data;
        b->cap  = new_cap;
    }
    memcpy(b->data + b->len, src, (size_t)n);
    b->len += n;
    b->data[b->len] = '\0';
    return 0;
}

/* -------------------------------------------------------------------------
 * Main CGI logic
 * ---------------------------------------------------------------------- */
int main(void)
{
    /* Environment variables */
    const char *game_host;
    const char *game_port_str;
    const char *server_id_str;
    int         game_port;

    /* CGI environment */
    const char *path_info;
    const char *request_uri;
    const char *request_method;
    const char *http_referer;
    const char *http_cookie;
    const char *http_accept_encoding;
    const char *http_accept_language;
    const char *http_user_agent;
    const char *http_host;
    const char *remote_addr;
    const char *query_string;
    const char *content_length_str;

    /* URL path to send */
    char  uri_buf[4096];
    char *uri;

    /* POST body */
    char  *post_body   = NULL;
    long   post_length = 0;

    /* Game socket */
    int    sock;

    /* Response accumulators */
    Buffer content_buf;
    Buffer cookie_buf;

    /* Receive loop */
    Packet pkt;
    char   chunk[MAX_MESSAGE_DATA_SIZE + 1];
    int    terminated = 0;
    int    loop_count = 0;

    /* -- Configuration -------------------------------------------------- */
    game_host     = getenv("ARCHSPACE_GAME_HOST");
    if (!game_host || !*game_host) game_host = DEFAULT_GAME_HOST;

    game_port_str = getenv("ARCHSPACE_GAME_PORT");
    game_port     = game_port_str ? atoi(game_port_str) : DEFAULT_GAME_PORT;
    if (game_port <= 0) game_port = DEFAULT_GAME_PORT;

    server_id_str = getenv("ARCHSPACE_SERVER_ID");
    if (server_id_str && atoi(server_id_str) > 0)
        g_server_id = (unsigned short)(0x0400 + atoi(server_id_str));

    /* -- CGI environment ------------------------------------------------ */
    path_info             = getenv("PATH_INFO");
    request_uri           = getenv("REQUEST_URI");
    request_method        = getenv("REQUEST_METHOD");
    http_referer          = getenv("HTTP_REFERER");
    http_cookie           = getenv("HTTP_COOKIE");
    http_accept_encoding  = getenv("HTTP_ACCEPT_ENCODING");
    http_accept_language  = getenv("HTTP_ACCEPT_LANGUAGE");
    http_user_agent       = getenv("HTTP_USER_AGENT");
    http_host             = getenv("HTTP_HOST");
    remote_addr           = getenv("REMOTE_ADDR");
    query_string          = getenv("QUERY_STRING");
    content_length_str    = getenv("CONTENT_LENGTH");

    /* Determine URI to send (mod_as.c make_url_send uses aRequest->uri).
     * Prefer PATH_INFO; fall back to REQUEST_URI with query stripped. */
    uri = NULL;
    if (path_info && *path_info) {
        uri = (char *)path_info;
    } else if (request_uri && *request_uri) {
        const char *q = strchr(request_uri, '?');
        if (q) {
            int plen = (int)(q - request_uri);
            if (plen >= (int)sizeof(uri_buf)) plen = (int)sizeof(uri_buf) - 1;
            memcpy(uri_buf, request_uri, (size_t)plen);
            uri_buf[plen] = '\0';
            uri = uri_buf;
        } else {
            uri = (char *)request_uri;
        }
    }
    if (!uri || !*uri) uri = "/";

    /* Read POST body if applicable (mod_as.c read_post / util_read) */
    if (request_method && strcmp(request_method, "POST") == 0) {
        if (content_length_str) {
            post_length = atol(content_length_str);
            if (post_length > 0 && post_length < 1024 * 1024) {
                post_body = (char *)malloc((size_t)(post_length + 1));
                if (post_body) {
                    long got = 0;
                    while (got < post_length) {
                        int r = (int)fread(post_body + got, 1,
                                           (size_t)(post_length - got), stdin);
                        if (r <= 0) break;
                        got += r;
                    }
                    post_body[got] = '\0';
                    post_length    = got;
                }
            }
        }
    }

    /* -- Connect to game server ----------------------------------------- */
    sock = game_connect(game_host, game_port);
    if (sock < 0) {
        cgi_error("The game server is currently unavailable. "
                  "Please try again in a few minutes.");
        if (post_body) free(post_body);
        return 0;
    }

    /* -- Send request packets (mod_as.c archspace_handler lines 640-685) - */

    /* MT_URL_SEND -- mandatory */
    if (send_string(sock, MT_URL_SEND, uri) < 0) goto send_error;

    /* MT_REFERER_SEND -- optional (mod_as.c line 649) */
    if (http_referer && *http_referer)
        if (send_string(sock, MT_REFERER_SEND, http_referer) < 0)
            goto send_error;

    /* MT_METHOD_SEND -- mandatory (mod_as.c line 651) */
    if (send_string(sock, MT_METHOD_SEND,
                    request_method ? request_method : "GET") < 0)
        goto send_error;

    /* MT_COOKIE_SEND -- optional (mod_as.c line 653) */
    if (http_cookie && *http_cookie)
        if (send_string(sock, MT_COOKIE_SEND, http_cookie) < 0)
            goto send_error;

    /* MT_ACCEPT_ENCODING_SEND -- optional (mod_as.c line 658) */
    if (http_accept_encoding && *http_accept_encoding)
        if (send_string(sock, MT_ACCEPT_ENCODING_SEND,
                        http_accept_encoding) < 0)
            goto send_error;

    /* MT_ACCEPT_LANGUAGE_SEND -- optional (mod_as.c line 660) */
    if (http_accept_language && *http_accept_language)
        if (send_string(sock, MT_ACCEPT_LANGUAGE_SEND,
                        http_accept_language) < 0)
            goto send_error;

    /* MT_USER_AGENT_SEND -- optional (mod_as.c line 662) */
    if (http_user_agent && *http_user_agent)
        if (send_string(sock, MT_USER_AGENT_SEND, http_user_agent) < 0)
            goto send_error;

    /* MT_HOST_NAME_SEND -- optional (mod_as.c line 664) */
    if (http_host && *http_host)
        if (send_string(sock, MT_HOST_NAME_SEND, http_host) < 0)
            goto send_error;

    /* MT_CONNECTION_INFO_SEND -- client IP (mod_as.c line 666) */
    if (remote_addr && *remote_addr)
        if (send_string(sock, MT_CONNECTION_INFO_SEND, remote_addr) < 0)
            goto send_error;

    /* MT_QUERY_SEND -- GET query string or POST body (mod_as.c line 671) */
    {
        const char *query_data = NULL;
        if (request_method && strcmp(request_method, "POST") == 0) {
            if (post_body && post_length > 0)
                query_data = post_body;
        } else {
            if (query_string && *query_string)
                query_data = query_string;
        }
        if (query_data)
            if (send_string(sock, MT_QUERY_SEND, query_data) < 0)
                goto send_error;
    }

    /* MT_GET_PAGE_REQUEST -- bare 8-byte header (mod_as.c line 676) */
    if (send_bare(sock, MT_GET_PAGE_REQUEST) < 0) goto send_error;

    /* -- Receive response ----------------------------------------------- */
    content_buf.data = (char *)malloc(CONTENT_INITIAL);
    cookie_buf.data  = (char *)malloc(4096);
    if (!content_buf.data || !cookie_buf.data) goto recv_error;

    content_buf.len  = 0;
    content_buf.cap  = CONTENT_INITIAL;
    content_buf.data[0] = '\0';

    cookie_buf.len   = 0;
    cookie_buf.cap   = 4096;
    cookie_buf.data[0] = '\0';

    /*
     * Receive loop.  We call pkt_recv() for each packet until we see
     * MT_TERMINATE_REQUEST (0x0000).  Matches net.h
     * receive_packet_from_gameserver() loop, capped at 1000 iterations
     * for safety (mod_as has a timeout-based cap of Count>60 with 1s
     * select; we rely on blocking I/O + socket timeout instead).
     */
    while (!terminated && loop_count < 1000) {
        unsigned short type;

        if (pkt_recv(sock, &pkt) < 0) break;
        type = pkt_type(&pkt);
        loop_count++;

        switch (type) {
        case MT_TERMINATE_REQUEST:
            /* End of response -- net.h line 803 */
            terminated = 1;
            break;

        case MT_CONTENT_SEND:
            /* Accumulate HTML body (net.h set_content) */
            {
                int n = extract_string_chunk(&pkt, chunk, sizeof(chunk));
                if (n > 0) buf_append(&content_buf, chunk, n);
            }
            break;

        case MT_SET_COOKIE_SEND:
            /* Accumulate cookie value (net.h set_cookie) */
            {
                int n = extract_string_chunk(&pkt, chunk, sizeof(chunk));
                if (n > 0) buf_append(&cookie_buf, chunk, n);
            }
            break;

        case MT_HEADER_SEND:
            /* mod_as.c has set_header() commented out (line 717);
             * we ignore these extra headers as the original does. */
            break;

        case MT_ERROR_SEND:
        default:
            /* Unknown or error packet -- skip */
            break;
        }
    }

    close(sock);
    if (post_body) free(post_body);

    /* -- Emit CGI response ----------------------------------------------- */
    if (!terminated || content_buf.len == 0) {
        /* Game produced no content: mirrors mod_as.c "game server down" path */
        free(content_buf.data);
        free(cookie_buf.data);
        cgi_error("The server has just gone down. This may have been caused "
                  "by your actions. Please report this to the Archspace "
                  "Customer Support Team.");
        return 0;
    }

    /* CGI headers */
    printf("Content-Type: text/html; charset=utf-8\r\n");
    printf("Pragma: no-cache\r\n");
    printf("Cache-Control: no-cache\r\n");
    printf("Expires: 0\r\n");

    /*
     * Set-Cookie handling.
     * mod_as.c set_cookie() (lines 869-968) splits the raw cookie string on
     * ';' and emits one Set-Cookie header per name=value pair.
     * We replicate that here with a simple in-place split.
     */
    if (cookie_buf.len > 0) {
        char *p_cookie = cookie_buf.data;
        char *token;
        char *saveptr = NULL;

        /* strtok_r is POSIX; use it to split on ';' */
        token = strtok_r(p_cookie, ";", &saveptr);
        while (token) {
            /* trim leading space (mod_as.c line 940: if (*Data==' ')++Data) */
            while (*token == ' ') token++;
            if (*token) {
                /* If value is non-empty emit as-is; the game already formats
                 * the cookie string fully (name=value; path=...) */
                printf("Set-Cookie: %s\r\n", token);
            }
            token = strtok_r(NULL, ";", &saveptr);
        }
    }

    /* Blank line separates headers from body */
    printf("\r\n");

    /* Body */
    fwrite(content_buf.data, 1, (size_t)content_buf.len, stdout);

    free(content_buf.data);
    free(cookie_buf.data);
    return 0;

send_error:
    close(sock);
    if (post_body) free(post_body);
    cgi_error("Could not communicate with the game server. "
              "Please try again shortly.");
    return 0;

recv_error:
    close(sock);
    if (post_body) free(post_body);
    free(content_buf.data);
    free(cookie_buf.data);
    cgi_error("Internal error: memory allocation failed.");
    return 0;
}
