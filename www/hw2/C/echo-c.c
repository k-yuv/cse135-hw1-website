#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>
#include <unistd.h>
#include <ctype.h>

#define MAX_INPUT 10000
#define MAX_LINE 256

void url_decode(char *dst, const char *src) {
    char a, b;
    while (*src) {
        if ((*src == '%') && ((a = src[1]) && (b = src[2])) && 
            (isxdigit(a) && isxdigit(b))) {
            if (a >= 'a') a -= 'a'-'A';
            if (a >= 'A') a -= ('A' - 10);
            else a -= '0';
            if (b >= 'a') b -= 'a'-'A';
            if (b >= 'A') b -= ('A' - 10);
            else b -= '0';
            *dst++ = 16*a+b;
            src+=3;
        } else if (*src == '+') {
            *dst++ = ' ';
            src++;
        } else {
            *dst++ = *src++;
        }
    }
    *dst++ = '\0';
}

void parse_query_string(char *query) {
    if (!query || strlen(query) == 0) {
        printf("(No data received)\n");
        return;
    }
    
    char *pairs = strdup(query);
    char *pair = strtok(pairs, "&");
    
    while (pair != NULL) {
        char *equals = strchr(pair, '=');
        if (equals) {
            *equals = '\0';
            char key[256], value[1024];
            url_decode(key, pair);
            url_decode(value, equals + 1);
            printf("%s: %s\n", key, value);
        }
        pair = strtok(NULL, "&");
    }
    free(pairs);
}

int main() {
    char *method = getenv("REQUEST_METHOD");
    char *content_type = getenv("CONTENT_TYPE");
    char *user_agent = getenv("HTTP_USER_AGENT");
    char *remote_addr = getenv("REMOTE_ADDR");
    char *query_string = getenv("QUERY_STRING");
    char *content_length_str = getenv("CONTENT_LENGTH");
    
    char hostname[256];
    gethostname(hostname, sizeof(hostname));
    
    time_t now = time(NULL);
    struct tm *t = localtime(&now);
    char datetime[64];
    strftime(datetime, sizeof(datetime), "%Y-%m-%d %H:%M:%S", t);
    
    // Print headers
    printf("Content-Type: text/plain\n\n");
    
    // Print response
    printf("============================================================\n");
    printf("ECHO RESPONSE - C\n");
    printf("============================================================\n\n");
    
    printf("REQUEST INFORMATION:\n");
    printf("------------------------------------------------------------\n");
    printf("Hostname:        %s\n", hostname);
    printf("Date/Time:       %s\n", datetime);
    printf("HTTP Method:     %s\n", method ? method : "Unknown");
    printf("Content-Type:    %s\n", content_type ? content_type : "text/plain");
    printf("User Agent:      %s\n", user_agent ? user_agent : "Unknown");
    printf("Client IP:       %s\n", remote_addr ? remote_addr : "Unknown");
    printf("\n");
    
    printf("RECEIVED DATA:\n");
    printf("------------------------------------------------------------\n");
    
    // Parse data based on method
    if (method && (strcmp(method, "GET") == 0 || strcmp(method, "DELETE") == 0)) {
        parse_query_string(query_string);
    } else if (method && (strcmp(method, "POST") == 0 || strcmp(method, "PUT") == 0)) {
        if (content_type && strstr(content_type, "application/json")) {
            // Read JSON data
            int content_length = content_length_str ? atoi(content_length_str) : 0;
            if (content_length > 0 && content_length < MAX_INPUT) {
                char *json_data = malloc(content_length + 1);
                fread(json_data, 1, content_length, stdin);
                json_data[content_length] = '\0';
                printf("Raw JSON Data:\n%s\n", json_data);
                free(json_data);
            } else {
                printf("(No data received)\n");
            }
        } else {
            // Read form data
            int content_length = content_length_str ? atoi(content_length_str) : 0;
            if (content_length > 0 && content_length < MAX_INPUT) {
                char *post_data = malloc(content_length + 1);
                fread(post_data, 1, content_length, stdin);
                post_data[content_length] = '\0';
                parse_query_string(post_data);
                free(post_data);
            } else {
                printf("(No data received)\n");
            }
        }
    } else {
        printf("(No data received)\n");
    }
    
    printf("\n");
    printf("============================================================\n");
    
    return 0;
}