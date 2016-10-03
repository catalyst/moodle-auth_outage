# Screencast Script #

## Preparation ##

1. Ensure the site has a nice theme and some content.

2. Create a future outage (not warning yet) for today or tomorrow.
   Use a duration of 1 hour for both the warning and the outage.
   You may need to fix the URLs below with the proper outage id (leave it copied).

4. Login as an admin, no outages should be showing yet. 

5. In another tab, go to: `https://github.com/catalyst/moodle-auth_outage`

## Demonstration ##

### Warning Bar ###

- *"Hi, I am Daniel from Catalyst IT Australia.
We developed this plugin as a need for an improved maintenance mode
to use with our clients, but we decided to share it with the open-source
community so feel free to comment, register issues and create pull requests in our
github account."* Show moodle page.

- Append `?auth_outage_preview=2&auth_outage_delta=-3600` to the address bar.

- *"This plugin warns users about scheduled maintenances or outages in Moodle."

- Change `auth_outage_delta` to `-15`.

- *"As we get closer to the outage period, a warning bar like this will show up.
This countdown will be displayed to all users and will change color once
the outage is about to start."*

- Outage should start, warning bar becomes red.

- *"At this point we have an ongoing outage and Moodle's maintenance mode may
automatically kick in or not, depending on the configuration.
If we are not using Moodle's maintenance mode, this page will keep pooling
the server to check if the outage is finished."*

- *"Once the outage is finished, you will see this message:"*

- Change `auth_outage_delta` to `3599`.

- *"... and after you resume browsing, it will simply go away."*

- Show calendar: *"When the administrator configures an outage, it is also added to the calendar."*

### Managing Outages ###

- *"Let's open the Manage Outages page:"

- Navigate to it.

- *"Here we can see Planned outages, which are ongoing or future outages,
and Outage history which shows all previous entries."*

- Click 'Create Outage'.

- *"The 'start date and time' is exactly what it says, when the outage starts."*

- *"The 'warning duration' is how long before it starts we should display the warning message."*

- *"The 'outage duration' is how long it is planned to last."*

- *"You can customise the 'title' and 'description' of the outage."*

- *"Now, this is important: if you check 'auto start maintenance mode', once the
outage starts it will activate Moodle Maintenance Mode. You will not be able to
access your site again until you manually disable the maintenance mode in your server."*

- *"Let's create an outage..."*

- Fill in some data and save.

- *"... and this is how it looks like. If you check the calendar..."*

- Show calendar.

- *"... you can see an event was also created to make sure everybody knows about it before it happens."*

### CLI - Command Line Interface ###

- *"If you are a system administrator you can use the CLI tools to incorporate it in your
scripts."

- With the browser open, execute: `php cli/create.php --help`

- `php cli/create.php` *"Let's see..."*
 
- `--autostart=no` *"Let's not use autostart, I don't want Moodle to close the system."*

- `-s=30` *"The outage starts in 30 seconds."*

- `-w=20` *"The warning will show 20 seconds before it starts, which means 10 seconds after I execute this."*

- `-d=600` *"This outage will last 10 minutes."*

- `-t="Very quick outage."` *"I will set a title, but I will leave the default description."*

- `--block` *"Now this is interesting. You will notice the script execution will
not return until the outage starts, which is good if you wanted to add more commands
after that, like a database backup.*"

- Execute.

- *"See that the script is not finishing? It is waiting for the outage to start."*

- *"Let's see if the warning shows up..."*

- Keep browsing until warning shows up.

- *"Good. Now let's wait until it starts.
You will see that the script will complete once it starts."*

- Wait.

- *"Cool. Now let's say we want to finish the outage before the one hour:"

- Execute `php cli/finish.php --active`

- *"And done. That was a quick overview of how the auth outage plugin works and how to use it."

### Static Page ###

- *"Ahh, one more thing. As suggested by Brendan, I will show how the static page looks
like once Moodle's maintenance mode is activated."*

- *"So, let's create an outage starting very soon, but this time we will autostart Moodle's maintenance mode."

- *"As we can see, this page is simple because at this point we have no access to Moodle, a database could
be upgrading, for example, so we cannot rely on any service. This is just a simple HTML page generated
before the system entered maintenance mode."

- *"This page will refresh automatically, in case it is left open, but once Moodle maintenance mode is disabled..."

- *"... we can navigate back to our website. Thank you!"
