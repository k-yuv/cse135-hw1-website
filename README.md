# --- HW 2 --- 
Team members:
Annejulia Milian
Dishita Joshi
Keyura Valalla

Grader access to server:
-Username: grader
-Password: Sanrio135Cse

# 3rd party page analytics
For the third party page analytics, we used Microsoft Clarity. We did research on different analytics tools and decided that Clarity was the best option because it is free and easy to set up.

# Note to Grader
The files linked from the website are under usr/lib/cgi-bin in the server. To make for more efficient grader, our source code for each link is under the www/hw2 directory in the repository, while our working code is in /cgi-bin.

# --- HW 1 --- 

# cse135-hw1-website
Team members:
Annejulia Milian
Dishita Joshi
Keyura Valalla

Grader access to server:
-Username: grader
-Password: Sanrio135Cse

Domain: cse135hw1.online

Usernames/Passwords for logging into site
Username: annejulia
Password: Sanrio135Cse

Username: dishita
Password: Sanrio135Cse

Username: keyura
Password: Sanrio135Cse

# Link to yourdomain.site -- Links to an external site
https://www.cse135hw1.online/
https://collector.cse135hw1.online/
https://reporting.cse135hw1.online/

# Details of Github auto deploy setup
Using GitHub actions, I created a deploy.yml file which runs the deploy script. This deploy script logs onto the deploy account that was created specifically to update the website contents. Everytime a commit is pushed to the repository, the script is triggered, in which it uploads the contents of the repository into the var/www/ directory.

# Login Information for Password Protection (Part 3 Step 4)
htpasswd dishita - Sanrio135Cse
htpasswd keyura - Sanrio135Cse
htpasswd annejulia - Sanrio135Cse

# Summary of changes to HTML file in DevTools after compression
Before compression the content length of the HTML file was 2641, and after compression it became 1006. So we clearly observed a compression of the HTML file.

# Summary of removing 'server' header (Partt 3 Step 6)
Naturally, Apache cannot change the server name, so I had to search how to change my server name permanently. I found this link https://www.howtoforge.com/changing-apache-server-name-to-whatever-you-want-with-mod_security-on-debian-6

Following the guide on this link I installed mod security, and I enabled it. It's known as a sever signature, so that is why you see CSE 135 Server.

# Homework 3 
Sessioning: To collect data on a specific user, we implemented getSessionId which is a unique number this user has. This ID is stored in storage. The user's activity is tracked like how much time they're idle for, mouse movement, keyboard clicks, etc. All the information in the payload is sent to an endpoint designated for our server. 