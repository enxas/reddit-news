## What it is

A simple script that gets most popular listings from Reddit. Tested with PHP 8.1.

## Why I made it

I spend too much time checking various subreddits for news, so I made this little script to compile most important ones. You can schedule it to run on specific times uning **Cron** for **Linux** or **Scheduled Task** for **Windows**.

## How it works

It fetches mosts popular listings from specified subreddits using Reddit API. You can find result in `outputs` directory.

## Setup

Make sure you have a Reddit account.  
Go [here](https://www.reddit.com/prefs/apps) and create an app. Make sure to select **script** option. Once created, you will see your app **client_id** and **client_secret**.  
Copy `config.example.json` file and rename it to `config.json`.  
In `config.json` fill out your Reddit data.  
You can configure what subreddits to fetch and how many listings in `config.json` file.  
Install dependencies with `composer install`  

Now you can run the script with command `php index.php` and see output in `output` directory

### Resources

https://towardsdatascience.com/how-to-use-the-reddit-api-in-python-5e05ddfd1e5c  
https://github.com/Scresat/Reddit-to-Notion