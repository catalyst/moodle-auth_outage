![GitHub Workflow Status (branch)](https://img.shields.io/github/actions/workflow/status/catalyst/moodle-auth_outage/ci.yml?branch=MOODLE_39_STABLE)

# Moodle Outage manager plugin
* [Version Support](#version-support)
* [What is this?](#what-is-this)
* [Moodle Requirements](#moodle-requirements)
* [Screenshots](#screenshots)
* [Installation](#installation)
* [Theme configuration](#theme-configuration)
* [How to use](#how-to-use)
* [Quick Guide](#quick-guide)
* [Why is it an auth plugin?](#why-it-is-an-auth-plugin)
* [Feedback and issues](#feedback-and-issues)

What is this?
-------------

This is a Moodle plugin which makes the student experience of planned outages nicer,
and provides extra tools for administrators and testers that help before and after the
outage window.

The main idea is that instead of an outage being a very booleon on/off situation,
this plugin creates the concept of graduated outages where at predefined times before
an outage and after, different levels of warning and access can be provided to students
and testers letting them know what is about to happen and why.

![image](https://user-images.githubusercontent.com/187449/149717343-1d2c5237-dbc6-4d2a-a08c-2bdb343e87d2.png)

Moodle Requirements
-------------------

This plugin will work out-of-the-box with Moodle 3.0 and Moodle 3.1.

If you have an older version of Moodle you can still make it work but you will
need to manually add one extra plugin, please check:
* https://github.com/catalyst/moodle-local_outage

Branches
--------
| Moodle version     | Totara          | Branch           | PHP  |
| ------------------ | --------------- | ---------------- | ---- |
| Moodle 3.9+        | Totara 13+      | MOODLE_39_STABLE | 7.2+ |
| Moodle 3.3 to 3.8  | Totara 11 to 12 | MOODLE_38_STABLE | 7.1+ |
| Moodle 2.7 to 3.2  |                 | MOODLE_32_STABLE | 5.5+ |
|                    | Totara up to 10 | TOTARA_10        | 5.5+ |

Screenshots
-----------

![Manage outages page with a scheduled outage warning.](docs/2016-09-28_screenshot_warning.png?raw=true)
Manage outages page with a scheduled outage warning.

![The warning bar during an ongoing outage.](docs/2016-09-28_screenshot_ongoing.png?raw=true)
The warning bar during an ongoing outage.

![The warning bar once the outage has ended.](docs/2016-09-28_screenshot_ended.png?raw=true)
The warning bar once the outage has ended.

Installation
------------

1. Install the plugin the same as any standard moodle plugin either via the
Moodle plugin directory, or you can use git to clone it into your source:

     `git clone git@github.com:catalyst/moodle-auth_outage.git auth/outage`

    Or install via the Moodle plugin directory:
    
     https://moodle.org/plugins/auth_outage

2. Then run the Moodle upgrade

If you have issues please log them in github here:

https://github.com/catalyst/moodle-auth_outage/issues

3. Go to `Dashboard ► Site administration ► Plugins ► Authentication ► Manage authentication`,
enable the `Outage manager` plugin and place it on the top.

4. If you need to use the IP Blocking, please add the following lines into your `config.php`
before the `require('/lib/setup.php')` call:

```
// Insert this after $CFG->dataroot is defined.
if (file_exists(__DIR__.'/auth/outage/bootstrap.php')) {
    require(__DIR__.'/auth/outage/bootstrap.php');
}
```

Theme configuration
-------------------

This plugin must work gracefully with your theme, but every theme can be different so it's impossible to get this right out of the box (other than with the default moodle theme Boost).

There is an admin setting which allows you to add or override and css to fix css issues. Typically these include properly pushing the page down when the outage notification bar is visible, including making this work with fixed headers and when the hamburger menu is open / closed and at different responsive breakpoints.

This can be found at:

`Dashboard / Site administration / Plugins / Authentication / Outage manager / Settings`



Custom Theme Additional SCSS
-------------------

Custom themes generally do not have the same `$navbar-height` variable set to 80px (MOODLE), therefore custom themes will not calculate the change in navbar height with page elements that calculate the navbar total height. 

Add the following SCSS For Moodle 3.11+

```
body.auth_outage {
    #page-wrapper {
        #nav-drawer {
            top: $navbar-height + 100px;
            height: calc(100% - (#{$navbar-height} + 100px));
        }
        #page {
            margin-top: $navbar-height + 100px;
        }
    }
    [data-region=right-hand-drawer].drawer {
        top: $navbar-height + 100px;
        height: calc(100% - (#{$navbar-height} + 100px));
    } 
}
```

Totara is a little different with version 13+ and no variables are used to set the `totaraNav` height 

Add the following CSS For Totara 13+

```
.totaraNav {
    margin-top: 100px;
}
.local_envbar .totaraNav {
    margin-top: 50px;
}
body.auth_outage #page {
    margin-top: 0;
}
```



How to use
----------

1. Go to `Dashboard ► Site administration ► Plugins ► Authentication ► Outage manager ► Manage` and set up your future outages.

2. *(optional)* Integrate your maintenance scripts using the CLI in `auth/outage/cli`.

Example of CLI usage:
```
$ php cli/create.php --help
Creates a new outage.

  -h,  --help               shows parameters help.
  -c,  --clone              clone another outage except for the start time.
  -a,  --autostart          must be Y or N, sets if the outage automatically triggers maintenance mode.
  -w,  --warn               how many seconds before it starts to display a warning.
  -s,  --start              in how many seconds should this outage start or unix time to start outage. Required.
  -d,  --duration           how many seconds should the outage last.
  -t,  --title              the title of the outage.
  -e,  --description        the description of the outage.
       --onlyid             only outputs the new outage id, useful for scripts.
  -b,  --block              blocks until outage starts.
```

Quick Guide
-----------

Please see [QUICKGUIDE.md](QUICKGUIDE.md) for step-by-step examples on
how to test and use the Outage Manager.

Why it is an auth plugin?
-------------------------

One of the graduated stages this plugin introduces is a 'tester only' mode which disables login for most normal users. This is conceptually similar to the maintenance mode but enables testers to login and confirm the state after an upgrade without needing full admin privileges. 


Feedback and issues
-------------------

Please raise any issues in github:

https://github.com/catalyst/moodle-auth_outage/issues

Pull requests are welcome :)

If you need anything urgently or would like to sponsor a feature please contact Catalyst IT Australia:

https://www.catalyst-au.net/contact-us
