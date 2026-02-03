#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <time.h>

static const char *getEnv(const char *IPAddress, const char *unknownIP) {
    const char *value = getenv(IPAddress);
    if (value == NULL) {
        return unknownIP;
    }
    return value;
}

int main(void) {
    const char *visitorIP = getEnv("REMOTE_ADDR", "Unknown IP Address");

    time_t now = time(NULL);
    char *timeString = ctime(&now);

    if (timeString != NULL) {
        size_t len = strlen(timeString);
        if (len > 0 && timeString[len - 1] == '\n') {
            timeString[len - 1] = '\0';
        }
    } else {
        timeString = "Unknown Time";
    }

    printf("Content-Type: application/json\r\n\r\n");

    printf("{\n");
    printf("  \"greeting\": \"Hello Vistor! Welcome to CSE 135 HW 2!\",\n");
    printf("  \"language\": \"C (CGI)\",\n");
    printf("  \"generatedAt\": \"%s\",\n", timeString);
    printf("  \"ipAddress\": \"%s\"\n", visitorIP);
    printf("}\n");

    return 0;
}