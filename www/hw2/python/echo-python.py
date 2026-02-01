#!/usr/bin/python3

import sys
import os

print("Cache-Control: no-cache")
print("Content-Type: text/html\n")

print("""<!DOCTYPE html>
<html>
<head>
    <title>General Request Echo</title>
</head>
<body>
<h1 align="center">General Request Echo</h1>
<hr>
""")

print("<p><b>HTTP Protocol:</b>" + os.environ["SERVER_PROTOCOL"] + "</p>")
print("<p><b>HTTP Method:</b>" + os.environ["REQUEST_METHOD"] + "</p>")
print("<p><b>Query String:</b>" + os.environ["QUERY_STRING"] + "</p>")


content_length = int(os.environ.get('CONTENT_LENGTH', 0))
form_data = sys.stdin.read(content_length)
bytes_read = len(form_data)

print("<p><b>Message Body:</b>" + form_data + "</p>")

print("""
</body>
</html>
""")
