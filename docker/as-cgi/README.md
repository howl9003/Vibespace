# as-cgi

CGI replacement for the `mod_as` Apache module used by the Archspace game.

## What it does

nginx/fcgiwrap invokes `as-cgi` for every `*.as` request.  The program:

1. Reads the HTTP request context from CGI environment variables.
2. Opens a TCP connection to the Archspace game server.
3. Sends the request details using the game's binary message protocol
   (the same wire format that `mod_as` used).
4. Collects the HTML response from the game server.
5. Writes a properly formed CGI response (headers + body) to stdout.

## Building

```
make
```

Requires only a C99 compiler (gcc) and POSIX sockets.  No external libraries.

## nginx / fcgiwrap configuration (example)

```nginx
location ~ \.as$ {
    include        fastcgi_params;
    fastcgi_pass   unix:/run/fcgiwrap.socket;
    fastcgi_param  SCRIPT_FILENAME /usr/local/lib/as-cgi;

    # Game server location (override defaults if needed)
    fastcgi_param  ARCHSPACE_GAME_HOST  127.0.0.1;
    fastcgi_param  ARCHSPACE_GAME_PORT  12350;
}
```

## Environment variables

| Variable              | Default     | Description                                         |
|-----------------------|-------------|-----------------------------------------------------|
| `ARCHSPACE_GAME_HOST` | `127.0.0.1` | IP address or hostname of the Archspace game server |
| `ARCHSPACE_GAME_PORT` | `12350`     | TCP port the game server listens on                 |
| `ARCHSPACE_SERVER_ID` | (none)      | Web server serial number; sets header `server` field to `0x0400 + ID`. Leave unset for default (0x0400). |

Standard CGI variables consumed: `PATH_INFO`, `REQUEST_URI`, `REQUEST_METHOD`,
`HTTP_REFERER`, `HTTP_COOKIE`, `HTTP_ACCEPT_ENCODING`, `HTTP_ACCEPT_LANGUAGE`,
`HTTP_USER_AGENT`, `HTTP_HOST`, `REMOTE_ADDR`, `QUERY_STRING`,
`CONTENT_LENGTH`.

## Session cookie forwarding

The `HTTP_COOKIE` environment variable is forwarded verbatim to the game
server as an `MT_COOKIE_SEND` message.  This includes the `as_session` cookie
the game uses to identify the logged-in player.  No cookie rewriting is
performed on the way in.

`Set-Cookie` values returned by the game are emitted as individual CGI
`Set-Cookie:` response headers, split on `;` separators (matching the
behaviour of `mod_as`'s `set_cookie()` function).

## Protocol reference

See `as-cgi.c` header comment for the full wire-format description, including
byte-level layout of the 8-byte message header and the TLV item encoding.
Every detail is cross-referenced to the exact lines in:

- `mod_as-2.0/net.h`
- `mod_as-2.0/mod_as.c`
- `src/libs/net/message.cc`
- `src/libs/cgi/connection.cc`
