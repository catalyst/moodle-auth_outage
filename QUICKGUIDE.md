# Quick Guide
 
 * [Installation](#installation)
 * [Basic Usage](#basic-usage)
 * [IP Blocking](#ip-blocking)

Installation
------------

Please check [README.md](README.md#installation)

Basic Usage
-----------

1) Create an outage in the future

    Go to 
    `Dashboard ► Site administration ► Plugins ► Authentication ► Outage manager ► Manage`
    and click `Create outage` to create an outage with the following settings:
    
    - Outage auto start: `false`
    - Warning duration: `1 hour`
    - Start date and time: `Somewhere around 2 hours from now`
    - Outage duration: `1 hour`
    - Title and Description: `Leave default`
    
    _You should not see any warning message yet._

1) Change that outage to activate the warning

    Edit the outage, set:
    
    - Start date and time: `Sometime in the future but less than 1 hour from now`
    
    _The warning bar with a countdown is displayed!_
    
    **What is happening now?**
    Nothing, this is just a warning for all users.

1) Change that outage so it becomes active

    Edit the outage, set:
    
    - Start date and time: `Sometime in the past but less than 1 hour ago`

    _The warning changes color and shows the outage estimated end time._

    **What is happening now?**
    Nothing yet, it is just a warning to all users that the system may be unstable but it is still online.
    That will change when we are using the IP Blocking feature.

1) Activate maintenance mode

    **Note:** This step will happen automatically if you create an outage with the option to `Auto start maintenance mode`.

    Execute the following command from your Moodle instalation directory:
    
    `php admin/cli/maintenance.php --enable`

    _Anyone who tries to use Moodle now will receive a maintenance message._

    **Note:** If the outage starts with the option `Auto start maintenance mode` checked, the maint mode won't exit automatically.
    It will need to be disabled through command line.

1) Perform the maintenance activities.

    At this point it is safe to perform the required maintenance as no one can use the system, not even admins.

1) Disable the maintenance mode

    `php admin/cli/maintenance.php --disable`

    _Your site is back, but the outage warning still shows the maintenance (unless the outage ending time has reached)._

1) End the outage

    Using the GUI, you can do it in a few different ways (choose one) 
    - Click 'Finish' at the warning bar
    - Click 'Finish' at the outage management page
    - Edit the outage and lower the duration so it is finished by now

    _The outage bar should disappear (you may need to refresh the page)._
    

IP Blocking
-----------

We will manage outages from the CLI this time, but it does not matter which way you create them.

Execute the commands from your Moodle instalation directory and leave the browser open so you can see the changes.

1) Enable IP Blocker and allow your IP Address

    Go to 
    `Dashboard ► Site administration ► Plugins ► Authentication ► Outage manager ► Settings`
    and set `Allowed IP list` to `127.0.0.1` (or your IP address if not local).

1) Create an outage

    This command will create an outage starting in 30 seconds, with a warning period of 15 seconds.
    It will automatically start (trigger maintenance mode).

    `php auth/outage/cli/create.php -w=20 -s=30 --autostart=Y`

    Refresh the page but you will not see anything yet.

1) Wait until it starts

    Keep refreshing the page, in less than 10 seconds it will display the warning.

    Wait until the outage starts.

1) Maintenance mode should be active

    You cannot browse anymore because Moodle's maintenance mode is active, no one can use the system until it is manually disabled.

1) Perform the maintenance activities.

    At this point it is safe to perform the required maintenance as no one can use the system, not even admins.

1) Disable maintenance mode.

    `php admin/cli/maintenance.php --disable`

1) Browse with an allowed IP

    **Do not finish the outage yet...**

    Because you are white-listed in the IP Blocking, you can test the site while it is not open for normal users.

1) Browse with a blocked IP

    Change the IP settings to a different IP (but do not leave it blank, which disables IP Blocking):

    Go to
    `Dashboard ► Site administration ► Plugins ► Authentication ► Outage manager ► Settings`
    and set `Allowed IP list` to `1.2.3.4`

    Save and refresh.
    
    _You should see the maintenance page until the outage period is over._

1) Finish the outage

    Finish the outage using the CLI:
    
    `php auth/outage/cli/finish.php --active`

    Now the site is back and available for everyone.
