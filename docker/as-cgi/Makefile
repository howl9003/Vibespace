CC      = gcc
CFLAGS  = -O2 -Wall -std=c99
TARGET  = as-cgi
SRC     = as-cgi.c

.PHONY: all clean

all: $(TARGET)

$(TARGET): $(SRC)
	$(CC) $(CFLAGS) -o $@ $<

clean:
	rm -f $(TARGET)
