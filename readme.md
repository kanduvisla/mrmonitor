# Mr. Monitor

This tool can help you to monitor your remote sites. It's build primary to do 2 things:

1. Ping a list of URL's
2. Check if the URL's respond properly

So if you have a spare, big fat 27" monitor + computer on your work, or a television hanging on the wall doing
nothing, consider running this puppy on it in Chrome presentation mode.

## Installation

Just put it on a simple webserver (or a local environment, like vagrant) and navigate to it. Also set a cronjob to
schedule your ping-session (every 5 minutes for example). This hasn't have to be the webserver by the way, a Mac with
php installed would be enough:

    $ php ping.php [csv-file]

This will output a result.csv-file, which in it's turn is read by the webserver. Oh yeah, and put up the volume on your
monitoring system :-)

## Under the hood

The following happens under the hood:

- It checks both www- and non-www domains, and checks if either one of them redirects to the other (to prevent duplicate content)
- It checks the response time of the server, and flags it if it's below 0.5s for first response and 2s for a complete page load.
- It checks if (on a 200 response code) the result is bigger than 1kb (I had this case once where the site would just show a blank page but not a error code).

## Room for improvement

The following ideas might be added on a later stage:

- Split the import CSV in multiple chunks (in case 5 minutes are not enough to ping all the sites)
- Crawl the sites (like with CasperJS for example) for broken links
- Remotely check if the server can still send e-mails
- Remotely check disk quota
- Remotely check other stuff (this can be endless...)
- Implement some sort of JavaScript screensaver (after all, we want it to be visible at all times, but we don't want to screw up our monitor)
- Send notifications on errors (mail? sms?)
- Link it with an app for your mobile
- ...your ideas...