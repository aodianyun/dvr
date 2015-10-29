#!/usr/bin/env python
#coding=utf-8

# Copyright (C) 2014, Aodian Cloud

import os
import base64
import json
import urllib2
import Queue
import threading
import time

#填入access_id
ACCESS_ID = ''

#填入access_key
ACCESS_KEY = ''

#接口地址
UPLOAD_API = 'http://upload.dvr.aodianyun.com/v2'

#线程数
THREAD_NUM = 5

#每个分片大小
PART_SIZE = 1 * 1024 * 1024

def main() :
    #文件路径
    fileName = 'test.mp3'

    #打开文件
    fp = open(fileName,'rb')

    #文件大小
    fp.seek(os.SEEK_SET, os.SEEK_END)
    fileSize = fp.tell()

    queue = Queue.Queue(0)

    if fileSize % PART_SIZE:
        partSizeList = [PART_SIZE] * (fileSize / PART_SIZE) + [fileSize % PART_SIZE]
    else:
        partSizeList = [PART_SIZE] * (fileSize / PART_SIZE)

    #分片编号
    partNum = 1
    offset = 0
    for partSize in partSizeList:
        #分片内容读取
        fp.seek(offset)
        part = fp.read(partSize)
        
        #上传分片
        param = {}
        param['access_id'] = ACCESS_ID
        param['access_key'] = ACCESS_KEY
        param['fileName'] = fileName
        param['part'] = base64.b64encode(part)
        param['partNum'] = partNum
        queue.put((UPLOAD_API+'/DVR.UploadPart',json.dumps(param,ensure_ascii=False)))

        partNum += 1
        offset += partSize

    #开启THREAD_NUM个线程
    threadPool = []
    for i in xrange(THREAD_NUM):
        thread = UploadPartWorker(queue)
        #线程开始处理任务
        thread.start()
        threadPool.append(thread)
    for thread in threadPool:
        thread.join()

    #等待所有任务完成
    queue.join()

    #完成上传,结束调用接口
    param = {}
    param['access_id'] = ACCESS_ID
    param['access_key'] = ACCESS_KEY
    param['fileName'] = fileName
    req = urllib2.Request(UPLOAD_API+'/DVR.UploadComplete',json.dumps(param,ensure_ascii=False))
    res = urllib2.urlopen(req)
    result = res.read()
    print result

class UploadPartWorker(threading.Thread):
    def __init__(self, queue):
        threading.Thread.__init__(self)
        self.queue = queue

    def run(self):
        while True:
            try:
                (upload_api,param) = self.queue.get(block=False)
                req = urllib2.Request(upload_api,param)
                res = urllib2.urlopen(req)
                result = res.read()
                print result
                if result:
                    result = json.loads(result)
                    if result and result['Flag'] == 100:
                        self.queue.task_done()
            except Queue.Empty:
                break
            except:
                self.queue.task_done()


if __name__ == '__main__':
    start = time.time()
    main()
    end = time.time()
    print "Upload Complete Time: %s seconds" % (end - start)