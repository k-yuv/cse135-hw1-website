#include <stdio.h>
#include <stdlib.h>
#include <string.h>

char *get_param_value(const char *data, const char *key) {
    if (!data || !key) return NULL;

    size_t key_len = strlen(key);
    const char *p = data;

    while (*p) {
        if (strncmp(p, key, key_len) == 0 && p[key_len] == '=') {
            p += key_len + 1;

            const char *end = strpbrk(p, ";&");
            size_t len = end ? (size_t)(end - p) : strlen(p);

            char *value = malloc(len + 1);
            if (!value) return NULL;

            strncpy(value, p, len);
            value[len] = '\0';
            return value;
        }

        const char *next = strpbrk(p, ";&");
        if (!next) break;
        p = next + 1;
    }

    return NULL;
}

char *get_request_data(void) {
    const char *method = getenv("REQUEST_METHOD");
    if (!method) return NULL;

    if (strcmp(method, "GET") == 0) {
        const char *qs = getenv("QUERY_STRING");
        if (!qs) return NULL;
        char *data = malloc(strlen(qs) + 1);
        if (!data) return NULL;
        strcpy(data, qs);
        return data;
    } else if (strcmp(method, "POST") == 0) {
        const char *len_str = getenv("CONTENT_LENGTH");
        if (!len_str) return NULL;
         int len = atoi(len_str);
        if (len <= 0) return NULL;

        char *data = malloc(len + 1);
        if (!data) return NULL;

        if (fread(data, 1, len, stdin) != (size_t)len) {
            free(data);
            return NULL;
        }

        data[len] = '\0';
        return data;
    }

    return NULL;
}

char *get_action(void) {
    const char *source = getenv("QUERY_STRING");
    if (!source) return NULL;
    return get_param_value(source, "action");
}

char *get_name_from_cookie(void) {
    const char *cookie = getenv("HTTP_COOKIE");
    if (!cookie) return NULL;
    return get_param_value(cookie, "name");
}

void send_headers(const char *cookie_line) {
    printf("Content-Type: text/html\r\n");
    if (cookie_line && cookie_line[0] != '\0') {
        printf("%s\r\n", cookie_line);
    }
    printf("\r\n");
}

void print_page_start(const char *title) {
    printf("<!DOCTYPE html>\n");
    printf("<html lang=\"en\">\n");
    printf("<head><meta charset=\"UTF-8\"><title>%s</title></head>\n", title);
    printf("<body>\n");
    printf("<h1>%s</h1>\n", title);
}

void print_page_end(void) {
    printf("<p><a href=\"/state/state-c-collect.html\">Go to collect page</a></p>\n");
    printf("<p><a href=\"/state/state-c-view.html\">Go to view page</a></p>\n");
    printf("</body>\n</html>\n");
}
int main(void) {
    char *action = get_action();
    char *request_data = NULL;
    char *name_value = NULL;

    if (action && strcmp(action, "save") == 0) {
        request_data = get_request_data();
        name_value = get_param_value(request_data, "name");

        char cookie_line[512] = "";
        if (name_value) {
            snprintf(cookie_line, sizeof(cookie_line),
                     "Set-Cookie: name=%s; Path=/; Max-Age=3600",
                     name_value);
        }

        send_headers(name_value ? cookie_line : NULL);

        print_page_start("C State Demo – Saved");

        if (name_value) {
            printf("<p>Saved your name as: <strong>%s</strong></p>\n", name_value);
        } else {
            printf("<p>No name was submitted.</p>\n");
        }

        print_page_end();
    }else if (action && strcmp(action, "view") == 0) {
        name_value = get_name_from_cookie();

        send_headers(NULL);

        print_page_start("C State Demo – View");

        if (name_value) {
            printf("<p>Your saved name is: <strong>%s</strong></p>\n", name_value);
        } else {
            printf("<p>You have not saved a name yet.</p>\n");
        }

        print_page_end();
    }
    else if (action && strcmp(action, "clear") == 0) {
        send_headers("Set-Cookie: name=; Path=/; Max-Age=0");

        print_page_start("C State Demo – Cleared");
        printf("<p>Your saved name has been cleared.</p>\n");
        print_page_end();
    }
    else {
        send_headers(NULL);

        print_page_start("C State Demo – Error");
        printf("<p>Unknown or missing action. Use ?action=save, ?action=view, or ?action=clear.</p>\n");
        print_page_end();
    }

    if (action) free(action);
    if (request_data) free(request_data);
    if (name_value) free(name_value);

    return 0;
}