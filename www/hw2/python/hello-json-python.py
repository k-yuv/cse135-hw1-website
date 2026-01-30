import json
import os
import datetime as datetime

#http headers
print("Cache-Control: no-cache")
print("Content-Type: application/json\n")

date = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
address = os.environ.get("REMOTE_ADDR", "")

message = {
    "title": "Hello, Python!",
    "heading": "Hello, Python!",
    "message": "This page was generated with the Python programming language",
    "time": date,
    "IP": address
}

print(json.dumps(message))