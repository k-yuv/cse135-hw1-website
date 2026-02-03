#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <time.h>

static const char *getEnv(const char *IPAddress, const char *unknownIP){
    const char *value = getenv(IPAddress);
    if (value == NULL) {
        return unknownIP;
    }
    return value;
}

int main(void){
    const char *visitorIP = getEnv("REMOTE_ADDR", "Unknown IP Address");

    time_t now = time(NULL);
    char *timeString = ctime(&now);

    if(timeString == NULL){
        fprintf(stderr, "Unknown time.\n");
    }

    printf("Content-Type: text/html\r\n\r\n");
    printf("<!DOCTYPE html>\n");
    printf("<html lang=\"en\">\n");
    printf("<head>\n");
    printf("  <meta charset=\"UTF-8\" />\n");
    printf("  <title>Hello HTML World</title>\n");
    printf("</head>\n");
    printf("<body>\n");

    printf("  <h1>Hello HTML World</h1>\n");
    printf("  <hr>\n");
    printf("  Hello World<br>\n");
    printf("  Language: C (CGI)<br>\n");
    printf("  This program was generated at: %s<br>\n", timeString);
    printf("  Your current IP address is: %s\n", visitorIP);

    printf("</body>\n");
    printf("</html>\n");

    return 0;
}