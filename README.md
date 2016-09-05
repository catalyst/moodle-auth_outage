<a href="https://travis-ci.org/catalyst/moodle-auth_outage">
<img src="https://travis-ci.org/catalyst/moodle-auth_outage.svg?branch=master">
</a>

# Outage Moodle Authentication Module

Moodle Outage is an auth plugin that will
* Allow for a configurable outage notification that will be displayed to users
* Allow for only a range of IP addresses to connect prior to the outage window


<a href="https://travis-ci.org/CatalystIT-AU/moodle-auth_basic">
<img src="https://travis-ci.org/CatalystIT-AU/moodle-auth_basic.svg?branch=master">
</a>

* [What is this?](#what-is-this)
* [Installation](#installation)
* [Feedback and issues](#feedback-and-issues)

What is this?
-------------

This is a Moodle plugin which makes the student expereince of planned outages nicer, and provides extra tools for administrators and testers that help before and after the outage window.

The main idea is that instead of an outage being a very booleon on/off situation, this plugin creates the concept of graduated outages where at predefined times before an outage and after, different levels of warning can be provided to students letting them know what is about to happen and why.


Why it is an auth plugin?
-------------------------

One of the graduated stages this plugin introduces is a 'tester only' mode. This is conceptually similar to the maintenance mode but enables testers to login and confirm the state after an upgrade without needing full admin privileges. 


Installation
------------

1. Install the plugin the same as any standard moodle plugin either via the
Moodle plugin directory, or you can use git to clone it into your source:

     git clone git@github.com:catalyst/moodle-auth_outage.git auth/basic

    Or install via the Moodle plugin directory:
    
     https://moodle.org/plugins/auth_outage

2. Then run the Moodle upgrade

If you have issues please log them in github here:

https://github.com/catalyst/moodle-auth_outage/issues

3. Go to Dashboard ► Site administration ► Plugins ► Authentication ► Manage authentication and enable the auth_outage plugin and make it the very first auth plugin

4. Go to Dashboard ► Site administration ► Plugins ► Authentication ► Outage ► Manage and set up your future outages


Feedback and issues
-------------------

Please raise any issues in github:

https://github.com/catalyst/moodle-auth_outage/issues

Pull requests are welcome :)

If you need anything urgently or would like to sponsor a feature please contact Catalyst IT Australia:

https://www.catalyst-au.net/contact-us
