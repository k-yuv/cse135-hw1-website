import os

print("Cache-Control: no-cache")
print("Content-Type: text/html\n")

print("""<!DOCTYPE html>
<html>
<head>
    <title>Environment Variables</title>
</head>
<body>
<h1 align="center">Environment Variables</h1>
<hr>
""")

for variable in sorted(os.environ):
    value = os.environ[variable]
    print(f"<b>{variable}:</b> {value}<br />")

print("""
</body>
</html>
""")
