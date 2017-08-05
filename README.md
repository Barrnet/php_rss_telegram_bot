# php_rss_telegram_bot
A simple RSS to Telegram Channel bot written in PHP.

# Requirement
Tested with PHP5.6 and PHP7.1, require curl library. There's no requirement for the telegram API because it simply use a GET request via HTTP.

# Configuration
Edit "*bot_config.php*" file with your favorite editor, this file contain all the variables used by the bot:
* *$token* must contain the apikey of your bot, if you don't have one you can register your bot with @BotFather (https://telegram.me/BotFather), directly on Telegram.
* *$chat* must contain the id of the channel, user chat or group who the bot should send messages. Bot must have write permission on that destination.
* *$rss* must contain the URL of the Feed RSS, from that url all the new messages will be converted and then sent to the chat.
* *$log_file* is the name of the log file
* *$pid_file* is the file where the bot write the pid process, can be used for multiple purpose.
* *$attesa* is the ammount of sleep time between requests

# How to use

Start bot.php with "php bot.php" command from CLI. It should be launched with screen, nohup or simply by attaching an ending "&" after the command. 
