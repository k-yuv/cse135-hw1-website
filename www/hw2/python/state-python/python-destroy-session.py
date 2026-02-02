#!/usr/bin/python3
import cgi
import http.cookies
import os
from pathlib import Path

print("Content-type: text/html\n")

# Parse CGI parameters
form = cgi.FieldStorage()

# Get session ID from cookie or parameter
cookie = http.cookies.SimpleCookie()
cookie_string = os.environ.get('HTTP_COOKIE', '')
if cookie_string:
    cookie.load(cookie_string)

sid = None
if 'SITE_SID' in cookie:
    sid = cookie['SITE_SID'].value
elif form.getfirst('sid'):
    sid = form.getfirst('sid')

# Delete session file if session ID exists
if sid:
    session_dir = Path("/tmp/sessions")
    session_file = session_dir / f"session_{sid}.pkl"
    
    if session_file.exists():
        session_file.unlink()  # Delete the file

print("<html>")
print("<head>")
print("<title>Python Session Destroyed</title>")
print("</head>")
print("<body>")
print("<h1>Session Destroyed</h1>")
print("<a href=\"/python-cgiform.html\">Back to the Python CGI Form</a><br />")
print("<a href=\"/cgi-bin/python-sessions-1.py\">Back to Page 1</a><br />")
print("<a href=\"/cgi-bin/python-sessions-2.py\">Back to Page 2</a>")
print("</body>")
print("</html>")