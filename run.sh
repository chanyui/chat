#! /bin/sh

pid=$(ps -ef | grep '/www/chatOnline/chat/run.php' | grep -v grep |awk '{print $2}')

if [ "${pid}" = "" ]
then
	nohup /www/chatOnline/chat/run.php &
fi

#exit 0
