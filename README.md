# cse135-hw1-website
Team members:
Annejulia Milian
Dishita Joshi
Keyura Valalla

Grader access:
-Username: grader
-Password: Sanrio135Cse

Domain: cse135hw1.online

Details of Github auto deploy setup:


Usernames/Passwords for logging into site
Username: annejulia
Password: Sanrio135Cse

Username: dishita
Password: Sanrio135Cse

Username: keyura
Password: Sanrio135Cse

# Link to yourdomain.siteLinks to an external site

# Details of Github auto deploy setup
Using GitHub actions, I created a deploy.yml file which runs the deploy script. This deploy script logs onto the deploy account that was created specifically to update the website contents. Everytime a commit is pushed to the repository, the script is triggered, in which it uploads the contents of the repository into the var/www/ directory.

# Login Information for Password Protection (Part 3 Step 4)
htpasswd dishita - Sanrio135Cse
htpasswd keyura - Sanrio135Cse
htpasswd annejulia - Sanrio135Cse

# Summary of changes to HTML file in DevTools after compression
Before compression the content length of the HTML file was 2641, and after compression it became 1006. So we clearly observed a compression of the HTML file.

# Summary of removing 'server' header (Partt 3 Step 6)
Naturally, Apache cannot change the server name, so I had to search how to hcnage my server name permanently. I found this link https://www.howtoforge.com/changing-apache-server-name-to-whatever-you-want-with-mod_security-on-debian-6

Following the guide on this link I installed mod security, and I enabled it. It's known as a sever signature, so that is why you see CSE 135 Server.