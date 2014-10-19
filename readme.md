Overview
-----------------------------------

magento.php - main file, you will need to run it in browser or with crontab<br/>
states.php – list of US states, we need this for conversion from “Illinois” to “IL”<br/>
settings.php – file with your settings (account credentials for ChannelAdvisor, e-mail,etc..);<br/>

Details
-----------------------------------
First of all i’ve got to say that ChannelAdvisor use SOAP for their API, it is really convenient. All this POSTS,GET,CURL,SHMURL,file_get_contents is a nice things but SOAP is a much better. ChannelAdvisor has a special website which contains all info that you would need http://developer.channeladvisor.com/.
For example this page: http://developer.channeladvisor.com/display/cadn/SubmitOrder for Order Submit<br/><br/>

Now about the the script. First thing that you will need to do, is to synchronize all your products with your ChannelAdvisor account:
1. check does SKU exists with call “DoesSkuExist”
2. synchronize your product with “SynchInventoryItem”
Once all SKU’s has been synchronized, the next step is to prepare and add sales with call “SubmitOrder”, once order being processed it will be marked, to skip it in the next run. Script also skip all orders with statuses “canceled” and “closed” all orders other statuses will be processed.Here the full list of magento order statuses: