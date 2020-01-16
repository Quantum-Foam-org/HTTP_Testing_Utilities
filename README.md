HTTP Testing Utilities
============================

This project requires https://github.com/malaimo2900/php_cli and https://github.com/malaimo2900/php_common to work.

It includes curlHeaderOutput.php, mysqlProfiler.php, spiderHTTPUrl.php and ipNetwork.php.

1.) curl Header Output will return the number of HTTP Location headers, the time the request took, print out the cookies and also log all HTTP headers to a file.
2.) mysql profiler utilizes the mysql profile and writes a csv of the profiled information.
3.) spider http url will sider a white listed set of a tag href attributes. The white list is located in config/config.ini
4.) ip network will process a ipv4 network and cidr and return the network information

Run these commands:

1.) mkdir http_testing_utilities

2.) cd http_testing_utilities

5.) git clone https://github.com/malaimo2900/MemcacheStats.git

6.) git clone https://github.com/malaimo2900/php_cli.git

7.) git clone https://github.com/malaimo2900/php_common.git

8.) cd HTTP_Testing_Utilities

9.)  Run one of the following:
* php curlHeaderOutput.php --url=http://google.com
* php spiderHTTPUrl.php --startUrl=http://www.openbsd.org (The spider requires the MySQL Schema located in the SQLSchema folder called http_spider.sql)
* php mysqlProfiler.php --sql1="SELECT * FROM spidered_site" --sql2="SELECT * FROM spidered_site" --profile=2 --file=outputFile
* php ipNetwork.php --cidr=16 --ip=10.10.10.0

Right now use tag v1.2 for borht php_commong and php_cli
