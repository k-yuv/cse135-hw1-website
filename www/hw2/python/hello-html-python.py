#!/usr/bin/python3

import os
import datetime as datetime

print("Cache-Control: no-cache")
print("Content-Type: text/html\n")

print("<!DOCTYPE html>")
print("<html>")
print("<head>")
print("<title>Hello CGI World</title>")
print("</head>")
print("<body>")

print("<h1 align=center>Hello HTML World</h1><hr/>")
print("<p>Hello World</p>")
print("<p>This page was generated with the Python programming langauge</p>")

date = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
print("<p>This program was generated at: " + date + "</p>")

# IP Address is an environment variable when using CGI
address = os.environ.get("REMOTE_ADDR", "")
print("<p>Your current IP Address is: " + address + "</p>")

print("</body>")
print("</html>")
