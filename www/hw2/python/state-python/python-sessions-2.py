#!/usr/bin/python3

import cgi
import http.cookies
import pickle
import os
from pathlib import Path

# print("Cache-Control: no-cache\n")

# Create session directory if it doesn't exist
session_dir = Path("/tmp/sessions")
session_dir.mkdir(exist_ok=True)

# Get the Session ID from the Cookie
cookie = http.cookies.SimpleCookie()
cookie_string = os.environ.get('HTTP_COOKIE', '')
if cookie_string:
    cookie.load(cookie_string)

session_id = cookie.get('CGISESSID')
if session_id:
    session_id = session_id.value
else:
    session_id = None

# Load session data if session exists
session_data = {}
if session_id:
    session_file = session_dir / f"session_{session_id}.pkl"
    if session_file.exists():
        try:
            with open(session_file, 'rb') as f:
                session_data = pickle.load(f)
        except:
            session_data = {}

# Access Stored Data
username = session_data.get("username")

print("Content-Type: text/html\n")

print("<html>")
print("<head>")
print("<title>Python Sessions</title>")
print("</head>")
print("<body>")

print("<h1>Python Sessions Page 2</h1>")

if username:
    print(f"<p><b>Name:</b> {username}</p>")
else:
    print("<p><b>Name:</b> You do not have a name set</p>")

print("<br/><br/>")
print("<a href=\"/cgi-bin/python-sessions-1.py\">Session Page 1</a><br/>")
print("<a href=\"/python-cgiform.html\">Python CGI Form</a><br />")
print("<form style=\"margin-top:30px\" action=\"/cgi-bin/python-destroy-session.py\" method=\"get\">")
print("<button type=\"submit\">Destroy Session</button>")
print("</form>")

print("</body>")
print("</html>")