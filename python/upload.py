#!/usr/bin/env python
#coding=utf-8

# Copyright (C) 2014, Aodian Cloud

import os
import base64
import json
import urllib2
import time

#填入access_id
ACCESS_ID = ''

#填入access_key
ACCESS_KEY = ''

#接口地址
UPLOAD_API = 'http://upload.dvr.aodianyun.com/v2'

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

    #分片编号
    partNum = 1

    offset = 0
    flag = 0
    while flag != 1:
        partSize = 0

        if offset + PART_SIZE > fileSize:
            partSize = fileSize - offset
        else:
            partSize = PART_SIZE

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
        req = urllib2.Request(UPLOAD_API+'/DVR.UploadPart',json.dumps(param,ensure_ascii=False))
        res = urllib2.urlopen(req)
        result = res.read()
        print result

        if result and flag == 0:
            result = json.loads(result)
            if result and result['Flag'] == 100:
                partNum += 1
                offset += partSize

        #完成上传
        if offset == fileSize:
            flag = 1
            param = {}
            param['access_id'] = ACCESS_ID
            param['access_key'] = ACCESS_KEY
            param['fileName'] = fileName
            req = urllib2.Request(UPLOAD_API+'/DVR.UploadComplete',json.dumps(param,ensure_ascii=False))
            res = urllib2.urlopen(req)
            result = res.read()
            print result

if __name__ == '__main__':
    start = time.time()
    main()
    end = time.time()
    print "Upload Complete Time: %s seconds" % (end - start)