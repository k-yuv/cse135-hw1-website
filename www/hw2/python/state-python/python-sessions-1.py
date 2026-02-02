#!/usr/bin/python3

import cgi
import http.cookies
import uuid
import pickle
import os
from pathlib import Path

# Create session directory if it doesn't exist
session_dir = Path("/tmp/sessions")
session_dir.mkdir(exist_ok=True)

# Get or create session ID from cookie
cookie = http.cookies.SimpleCookie()
cookie_string = os.environ.get('HTTP_COOKIE', '')
if cookie_string:
    cookie.load(cookie_string)

session_id = cookie.get('CGISESSID')
if session_id:
    session_id = session_id.value
else:
    session_id = str(uuid.uuid4())

# Session file path
session_file = session_dir / f"session_{session_id}.pkl"

# Load existing session data or create new
if session_file.exists():
    with open(session_file, 'rb') as f:
        session_data = pickle.load(f)
else:
    session_data = {}

# Parse CGI parameters
form = cgi.FieldStorage()

# Get username from session or form
username = session_data.get('username') or form.getfirst('username')
if username:
    session_data['username'] = username

# Save session data
with open(session_file, 'wb') as f:
    pickle.dump(session_data, f)

# Set cookie and print headers
output_cookie = http.cookies.SimpleCookie()
output_cookie['CGISESSID'] = session_id
output_cookie['CGISESSID']['path'] = '/'

print(output_cookie.output())
print("Content-Type: text/html\n")  # IMPORTANT: Added Content-Type header

print("<html>")
print("<head>")
print("<title>Python Sessions</title>")
print("</head>")
print("<body>")

print("<h1>Python Sessions Page 1</h1>")

if username:
    print(f"<p><b>Name:</b> {username}</p>")
else:
    print("<p><b>Name:</b> You do not have a name set</p>")

print("<br/><br/>")
print("<a href=\"/cgi-bin/python-sessions-2.py\">Session Page 2</a><br/>")
print("<a href=\"/python-cgiform.html\">Python CGI Form</a><br />")
print("<form style=\"margin-top:30px\" action=\"/cgi-bin/python-destroy-session.py\" method=\"get\">")
print("<button type=\"submit\">Destroy Session</button>")
print("</form>")

print("</body>")
print("</html>")