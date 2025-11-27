

# pip install beautifulsoup4 lxml


from urllib.parse import urlparse
from bs4 import BeautifulSoup
import requests
import json
from datetime import datetime
import time
import pymysql

#数据库操作
def mysql_do(sql:str ,type:int):
    if type == 0 : #初始化数据库
        db = pymysql.connect(host="127.0.0.1", user="106", port=3306, password="563151abe9654",database="106")
        return db
    
    elif type == 1 : #查询
        try:
            #创建数据库
            mysqldb = mysql_do('',0)
            cursor = mysqldb.cursor()
            # 执行SQL语句
            cursor.execute(sql)
            # 获取所有记录列表
            results = cursor.fetchall()
            # print("查询成功")
            # 返回查询数据
            return results
        except Exception as e:
            # 发生错误时回滚
            print("执行MySQL1: %s 时出错：%s" % (sql, e))   
    
    elif type == 2 : #插入 更新
        
        #插入  sql = INSERT INTO EMPLOYEE(FIRST_NAME,LAST_NAME, AGE, SEX, INCOME) VALUES ('Mac', 'Mohan', 20, 'M', 2000)
        #更新  sql = "UPDATE EMPLOYEE SET AGE = AGE + 1 WHERE SEX = '%c'" % ('M')
        try:
            #创建数据库
            mysqldb = mysql_do('',0) 
            cursor = mysqldb.cursor()
            # 执行sql语句
            cursor.execute(sql)
            # 提交到数据库执行
            mysqldb.commit()
            # print("提交成功")
        except Exception as e:
            # 发生错误时回滚
            mysqldb.rollback()
            print("执行MySQL2: %s 时出错：%s" % (sql, e)) 


def getNew():

    url = "https://api.cj.sina.cn/transmit?pid=finance_wap_proxy_fl_1663832597&smartFlow=sinafinance_forex_yw_feed&up=0&pageSize=20&callback=sinajp_17289633148682737219845672276"

    data_str = requests.get(url).text
    data_str = data_str.replace(");","")
    data_str = data_str.replace("sinajp_17289633148682737219845672276(","")
    spot_data = json.loads(data_str)

    for dic in spot_data['data']:
        # print(dic)
        # {'_isFixed': 0, 'allCIDs': [56977, 257, 51894, 56975, 57028, 56416, 57026, 76554, 258, 51070, 76526, 76549, 210899, 90643, 76601, 56982, 56986, 76605], 
        # 'appLiveconid': 0, 'author': '', 'comment_count': 0, 'commentid': 'cj:comos-ncsrcxx1255499:0', 
        # 'commentinfo': 
        # {'qreply': 0, 'qreply_show': 0, 'show': 0, 'thread_show': 0, 'total': 0}, 
        # 'content-type': 'default', 'cre': 'fantian', 'ctime': 1728961250, 
        # 'docid': 'ncsrcxx1255499', 'f_docid': 'comos:ncsrcxx1255499', 
        # 'info': 'eabb59ec9faf38f592406f3e9f494404|25|1||0|none|1728964342|0||||||||||||comos:ncsrcxx1255499', 
        # 'is_ad': 0, 'loc': 1, 'media': '市场资讯', 'mod': 'finapp', 'short_intro': '', 
        # 'smartIndex': 'top', 'stitle': '', 
        # 'surl': 'https://finance.sina.com.cn/stock/hkstock/hkgg/2024-10-15/doc-incsrcxx1255499.shtml?cre=fantian&mod=finapp&loc=1&r=25&doct=0&rfunc=0&tj=none&tr=25', 
        # 'thumb': '', 'thumbs': [], 
        # 'timestamp': 1728961250, 'title': '交银国际：美国9月通胀虽超预期 但仍支持小幅减息', 
        # 'type': 1, 'url': 'https://finance.sina.com.cn/stock/hkstock/hkgg/2024-10-15/doc-incsrcxx1255499.shtml', 
        # 'uuid': 'eabb59ec9faf38f592406f3e9f494404', 'video_id': '', 
        # 'wapurl': 'https://finance.sina.cn/hkstock/ggpj/2024-10-15/detail-incsrcxx1255499.d.html'}
        ctime = dic['ctime']
        if int(ctime) == 0:
            print('无创建时间')
            continue

        title = dic['title']
        if str(ctime) == '':
            print('无标题')
            continue

        thumb = dic['thumb']
        if str(thumb) == '':
            print('无图片')
            continue

        sql = "SELECT id FROM fa_news WHERE title = '" + str(title) + "';"
        data = mysql_do(sql,1)
        if data:
            print('存在')
            continue

        webPage1 = requests.get(dic['url'])
        
        webPage1.encoding = 'utf-8'
        # print(str(webPage1))
        html1 = webPage1.text.replace("\'","'")
        soup1 = BeautifulSoup(html1, 'html.parser')
        # print(str(soup1))
        content = ''
        for article in soup1.find_all(attrs={'class':'article'}) :  
            for article in article.find_all('p') :  

                content = content + '\n' + article.get_text()
                

        if str(content) == '':
            print('无内容')
            continue


        json_dic = {'title':title,'ctime':ctime,'thumb':thumb,'content':content}
        # print(json_dic)

        insert_sql = "INSERT INTO fa_news(title,createtime,updatetime,image,content,label)" \
            "VALUES ( '" + str(title) + "', '" + str(ctime) + "', '"+ str(ctime) + "', '" + str(thumb) + "', '" + str(content)+ "', '" + str('HOT') + "');"
        # print(insert_sql)
        mysql_do(insert_sql,2)
        print('添加成功')


    
if __name__ == '__main__':
    mysqldb = mysql_do('',0) #创建数据库
    cursor = mysqldb.cursor()

    while True:
        getNew()
        time.sleep(30)
        
        
