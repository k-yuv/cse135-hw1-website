#include <stdlib.h>
#include <stdio.h>

extern char **environmentVariables;

int main(void) {
    printf("Content-Type: text/html\r\n\r\n");
    printf("<!DOCTYPE html>\n");
    printf("<html lang=\"en\">\n");
    printf("<head>\n");
    printf("  <meta charset=\"UTF-8\" />\n");
    printf("  <title>Environment Variables</title>\n");
    printf("</head>\n");
    printf("<body>\n");

    printf("  <h1>Environment Variables</h1>\n");
    printf("  <hr>\n");

    printf("  <pre>\n");
    for (char **env = environ; *env != NULL; env++) {
        printf("%s\n", *env);
    }
    printf("  </pre>\n");

    printf("</body>\n");
    printf("</html>\n");

    return 0;
}