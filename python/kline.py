import json
import config
import time
import random
import requests
import threading
from datetime import datetime
import pytz

# 时间延迟
def sleepTime(t, tip):
    time.sleep(0.5)
    if (t > 10):
        for i in range(1, 11):
            print(str(t * (10 - i) / 10.0) + tip)
            time.sleep(t / 10.0)
    else:
        print(str(t) + tip)
        time.sleep(t)


def modify_last_decimal_digit(number):
    # 将数字转换为字符串，以便于处理小数部分
    number_str = str(number)
    
    # 查找小数点的位置
    if '.' in number_str:
        decimal_index = number_str.index('.')
        # 获取小数部分
        decimal_part = number_str[decimal_index + 1:]
        
        if decimal_part:  # 确保小数部分不为空
            # 获取最后一位数字
            last_digit = int(decimal_part[-1])
            
            # 随机选择加3或减3
            operation = random.choice([-3, 3])
            
            # 计算新的最后一位数字
            new_last_digit = last_digit + operation
            
            # 确保最后一位数字在0-9之间
            if new_last_digit < 0:
                new_last_digit = 0
            elif new_last_digit > 9:
                new_last_digit = 9
            
            # 用新的最后一位数字替换原来的最后一位
            modified_decimal_part = decimal_part[:-1] + str(new_last_digit)
            modified_number = number_str[:decimal_index + 1] + modified_decimal_part
            return modified_number
    
    # 如果没有小数部分，返回原数
    return number
    

# 新浪数据
def sina(symbol, sytype, tiems, t_type):
    timestr = str(int(time.time() * 1000))
    # 新浪 k线 接口
    # 1分 https://vip.stock.finance.sina.com.cn/forex/api/jsonp.php/var _fx_susdjpy_5_1681959435893=/NewForexService.getMinKline?symbol=fx_susdjpy&scale=5&datalen=1440
    # 1日 https://stock2.finance.sina.com.cn/futures/api/jsonp.php/var _XAU2023_4_19=/GlobalFuturesService.getGlobalFuturesDailyKLine?symbol=XAU&_=2023_4_19&source=web

    # 1分 5分 30分 60分
    url = 'https://vip.stock.finance.sina.com.cn/forex/api/jsonp.php/var _' + symbol + '_' + t_type + '_' + timestr + '=/NewForexService.getMinKline?symbol=' + symbol + '&scale=' + t_type + '&datalen=1440'
    # url = 'https://api-q.fx678img.com/histories.php?symbol=' + symbol + '&limit=1000&resolution=' + resolution + '&codeType=' + codeType

    # 请求头
    headers = {
        "Referer": "https://finance.sina.com.cn/money/forex/hq/USDJPY.shtml",
    }

    # 发起请求
    response = requests.post(url, headers=headers)

    # 请求成功
    if response.status_code == 200:

        print(tiems + ' ' + sytype + ' sina 数据获取成功！')

        # var _fx_susdjpy_1_1681962420688=
        jsonstr = response.text.replace("var _" + symbol + '_' + t_type + '_' + timestr + "=(", "")
        jsonstr = jsonstr.replace(");", "")
        jsonstr = jsonstr.replace("/*<script>location.href='//sina.com';</script>*/", "")
        jsonstr = jsonstr.replace("\n", "")
        # print("var_XAU_" + t_type + "_" + timestr + "=(")
        # print(jsonstr[0:130])
        # 数据转json格式
        redata = json.loads(jsonstr)
        
        if not redata:
            print('数据获取错误 ----------------- ' + str(redata) + '--------------')
            return

        # 上传key
        key_code = sytype + '_stock_' + tiems  # GOLD_stock_1min

        # 数据
        result = {}

        # 定义容器
        k_dic = {}

        # 从最后一位开始取
        for dic in redata:
            # 交易量
            volume = random.randint(20000, 80000)
            # 时间戳 2023-04-18 18:20:00
            
            timeStamp = get_time_stamp(dic['d']) * 1000
            # timeStamp = int(time.mktime(time.strptime(dic['d'], "%Y-%m-%d"))) * 1000 + 3600000
            # 组装数据
            result = {'time': timeStamp, 'open': dic['o'], 'close': dic['c'], 'high': dic['h'],
                                'low': dic['l'], 'volume': volume}
            k_dic[timeStamp] = result
        # print(k_dic)

        # 上传到rds
        config.r.set(key_code, json.dumps(k_dic))


        # 1分钟顺便更新行情
        if t_type == '1':
            p_result = {
                "price_high": result['high'],
                "price_low": result['low'],
                "price": modify_last_decimal_digit(float(result['close'])),
                "vol": result['volume'],
                "open_price": result['open'],
                "price_zf": result['close'],
                "price_pre": result['close'],
            }
            cache = {}
            cache[sytype] = p_result
            json_data = config.r.get("updateProduct")
            if json_data != None:
                list = json.loads(json_data)
                list[sytype] = p_result
                cache = list
    
            config.r.set("updateProduct", json.dumps(cache))
            print("行情写入成功")
            
        print("--------------完成一次--------------")


# 上海时间转时间戳
def get_time_stamp(time_str):
    # 输入时间字符串和时间格式
    time_format = "%Y-%m-%d %H:%M:%S"  # 时间字符串格式
    
    # 将时间字符串解析为 datetime 对象
    dt = datetime.strptime(time_str, time_format)
    
    # 创建上海时区
    shanghai_tz = pytz.timezone('Asia/Shanghai')
    
    # 将 datetime 对象设为上海时区
    dt_shanghai = shanghai_tz.localize(dt)
    
    # 转换为时间戳
    timestamp = int(dt_shanghai.timestamp())
    
    return timestamp


# 新浪数据
def sina_day(symbol, sytype, tiems, t_type):
    timestr = time.strftime("%Y_%m_%d", time.localtime())
    # print(timestr)
    # 新浪 k线 接口
    # 1分 https://stock2.finance.sina.com.cn/futures/api/openapi.php/GlobalFuturesService.getGlobalFuturesMinLine?symbol=XAU&callback=var%20t1hf_XAU=
    # 1日 https://vip.stock.finance.sina.com.cn/forex/api/jsonp.php/var _fx_susdjpy2023_4_20=/NewForexService.getDayKLine?symbol=fx_susdjpy&_=2023_4_20

    # 1分 5分 30分 60分 
    url = 'https://vip.stock.finance.sina.com.cn/forex/api/jsonp.php/var _' + symbol + timestr + '=/NewForexService.getDayKLine?symbol=' + symbol + '&_=' + timestr + '&source=web'
    # url = 'https://api-q.fx678img.com/histories.php?symbol=' + symbol + '&limit=1000&resolution=' + resolution + '&codeType=' + codeType

    # 请求头
    headers = {
        "Referer": "https://finance.sina.com.cn/",
    }

    # 发起请求
    response = requests.post(url, headers=headers)

    # 请求成功
    if response.status_code == 200:

        print(tiems + ' ' + sytype + ' sina 数据获取成功！')

        # print(response.text)
        jsonstr = response.text.replace("var _" + symbol + timestr + "=(", "")
        jsonstr = jsonstr.replace(");", "")
        jsonstr = jsonstr.replace("/*<script>location.href='//sina.com';</script>*/", "")
        jsonstr = jsonstr.replace("\n", "")
        jsonstr = jsonstr.replace("\"", "")
        # 2023-04-17,133.81200,133.70700,134.57200,134.42600,|
        # 2023-04-18,134.42800,133.86000,134.70600,134.07400,|
        # 2023-04-19,134.07000,133.95000,135.13800,134.60100,|
        # 2023-04-20,134.61300,134.59700,134.97500,134.77800");

        # print(jsonstr[0:130])

        # 上传key
        key_code = sytype + '_stock_' + tiems  # GOLD_stock_1min

        # 定义容器
        k_dic = {}

        # 从最后一位开始取  时间(最新最后面)

        arr = jsonstr.split('|')
        for i in arr:
            dic = i.split(',')
            # 交易量
            volume = random.randint(20000, 80000)
            
            # 时间戳 2023-04-18 
            timeStamp = get_time_stamp(str(dic[0]) + ' 00:00:00') * 1000

            # 组装数据
            k_dic[timeStamp] = {'time': timeStamp, 'open': dic[1], 'close': dic[4], 'high': dic[3], 'low': dic[2],
                                'volume': volume}

        # print(k_dic)

        # 上传到rds
        config.r.set(key_code, json.dumps(k_dic))

        print("--------------完成一次--------------")


# 主程序
def main(type):

    statime = int(time.time())
    oktime = int(time.time())
    while True:
        # print(oktime)
    
        # 每分钟
        if (oktime % 60 == 0 or oktime == statime) and type == '1':
            time.sleep(5)
            p_data = config.product
            for key in p_data.keys():
                sina('fx_' + key, key, '1min', '1')
    
        # 5分钟
        if (oktime % 120 == 0 or oktime == statime) and type == '2':
            time.sleep(5)
            p_data = config.product
            for key in p_data.keys():
                sina('fx_' + key, key, '5min', '5')
    
        # 30分钟
        if (oktime % 300 == 0 or oktime == statime) and type == '3':
            time.sleep(5)
            p_data = config.product
            for key in p_data.keys():
                sina('fx_' + key, key, '30min', '30')
    
        # 60分钟
        if (oktime % 300 == 0 or oktime == statime) and type == '4':
            time.sleep(5)
            p_data = config.product
            for key in p_data.keys():
                sina('fx_' + key, key, '1hour', '60')
    
        # 1天
        if (oktime % 300 == 0 or oktime == statime) and type == '5':
            time.sleep(5)
            p_data = config.product
            for key in p_data.keys():
                sina_day('fx_' + key, key, '1day', '5')

        # 10分钟脚本
        if oktime - statime > 24 * 60 * 60:
            break
        # 延迟1s
        time.sleep(0.5)
        # 跑脚本任务
        oktime = int(time.time())


if __name__ == '__main__':

    
    # 创建线程
    thread1 = threading.Thread(target=main, args=("1"))
    thread2 = threading.Thread(target=main, args=("2"))
    thread3 = threading.Thread(target=main, args=("3"))
    thread4 = threading.Thread(target=main, args=("4"))
    thread5 = threading.Thread(target=main, args=("5"))



    # 启动线程
    thread1.start()
    thread2.start()
    thread3.start()
    thread4.start()
    thread5.start()

    
    # 等待所有线程完成
    thread1.join()
    thread2.join()
    thread3.join()
    thread4.join()
    thread5.join()


    print("线程已停止")
    # exit()
    # while True:
    #     try:
            
    #         # print('=============')

    #     except:
    #         print('======错误=======')
    #         time.sleep(60)
    #         pass
    exit()
