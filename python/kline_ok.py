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
    

# OK数据
def okx_data(symbol, redis_time,okx_time):

#     {
#     "code":"0",
#     "msg":"",
#     "data":[
#      [
#         "1597026383085",
#         "3.721",
#         "3.743",
#         "3.677",
#         "3.708",
#         "8422410",
#         "22698348.04828491",
#         "12698348.04828491",
#         "1"
#     ],

    # ts	String	开始时间，Unix时间戳的毫秒数格式，如 1597026383085
    # o	String	开盘价格
    # h	String	最高价格
    # l	String	最低价格
    # c	String	收盘价格
    # vol	String	交易量，以张为单位
    # 如果是衍生品合约，数值为合约的张数。
    # 如果是币币/币币杠杆，数值为交易货币的数量。
    # volCcy	String	交易量，以币为单位
    # 如果是衍生品合约，数值为交易货币的数量。
    # 如果是币币/币币杠杆，数值为计价货币的数量。
    # volCcyQuote	String	交易量，以计价货币为单位
    # 如 BTC-USDT和BTC-USDT-SWAP，单位均是USDT
    # BTC-USD-SWAP单位是USD
    # confirm	String	K线状态
    # 0：K线未完结
    # 1：K线已完结
#     ]
# }


    # url = "https://www.okx.com/api/v5/market/history-candles"
    url = "https://www.okx.com/api/v5/market/candles"
    
    # 请求参数
    params = {
        'instId':  symbol + '-USDT',         # 交易对，例如 'BTC-USDT'
        'bar': okx_time,          # K线周期 [1s/1m/3m/5m/15m/30m/1H/2H/4H]  [6H/12H/1D/2D/3D/1W/1M/3M]
        # 'after': start_time,      # 开始时间
        # 'before': end_time,       # 结束时间
        'limit': 300              # 返回的最大条数（200 是最大限制）
    }
    
    # print(params)
    # 发送请求
    response = requests.get(url, params=params)
    
    # 判断是否成功获取数据
    if response.status_code == 200:
        
        
        print(redis_time + ' | ' + symbol + ' | OKX 数据获取成功！')
        
        data= response.json()
        
        # {'code': '51001', 'msg': 'Instrument ID does not exist', 'data': []}
        if data['code'] != '0':
            print('数据获取错误 ------- ' + data['msg'] + '---------')
            return
        
        # 上传key
        key_code = symbol + '_stock_' + redis_time  # GOLD_stock_1min

        redis_data = config.r.get(key_code)
        # print(redis_data)
        
        # 定义字段
        k_dic = {}

        k_list = []
        
        # 有历史数据
        if redis_data:
            k_list = json.loads(redis_data)
            
            if isinstance(k_list, dict):
                # 所有key
                all_key =  k_list.keys()
                # 排序
                all_key = sorted(all_key)
                # 删除300条
                if len(all_key) > 2100:
                    del all_key[0:300]
                    
                # 写入字典
                for dic in all_key:
                    k_dic[str(dic)] = k_list[str(dic)]
            elif isinstance(data, list):
                
                # 大于2100条 删除300条
                if len(k_list) > 2100:
                    del k_list[0:300]
                    
                # 写入字典
                for dic in k_list:
                    k_dic[str(dic['time'])] = dic
            
    
    
        # 数据
        result = {}
        
        # 第一个时间最新 所以倒叙取
        for item in reversed(data['data']):
            
            # ts	String	开始时间，Unix时间戳的毫秒数格式，如 1597026383085 0
            # o	String	开盘价格 1
            # h	String	最高价格 2
            # l	String	最低价格 3
            # c	String	收盘价格 4
            # vol	String	交易量，以张为单位 5
            # 如果是衍生品合约，数值为合约的张数。
            # 如果是币币/币币杠杆，数值为交易货币的数量。
            # volCcy	String	交易量，以币为单位 6
            # 如果是衍生品合约，数值为交易货币的数量。
            # 如果是币币/币币杠杆，数值为计价货币的数量。
            # volCcyQuote	String	交易量，以计价货币为单位 7
            # 如 BTC-USDT和BTC-USDT-SWAP，单位均是USDT
            # BTC-USD-SWAP单位是USD 
            # confirm	String	K线状态 8
            # 0：K线未完结
            # 1：K线已完结
            timeStamp = str(item[0])
            # print(timeStamp)
            
            # 组装数据
            reg = {
                'time': timeStamp, 
                'open': str(item[1]), 
                'close': str(item[4]), 
                'high': str(item[2]), 
                'low': str(item[3]),
                'volume': str(item[5]),
            }
            k_dic[timeStamp] = reg
            result = reg
        
        # print(len(k_dic))
        # 上传到rds
        config.r.set(key_code, json.dumps(k_dic))

        # 1分钟顺便更新行情
        if okx_time == '1m':
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
            cache[symbol] = p_result
            json_data = config.r.get("updateProduct")
            if json_data != None:
                list = json.loads(json_data)
                list[symbol] = p_result
                cache = list
    
            config.r.set("updateProduct", json.dumps(cache))
            print("行情写入成功")
            
        print("--------------完成一次--------------")
        
    else:
        return None
    
    
# 主程序
def main(type):

    statime = int(time.time())
    oktime = int(time.time())
    while True:
        # print(oktime)
    
        # 每分钟
        if (oktime % 20 == 0 or oktime == statime) and type == '1':
            time.sleep(5)
            p_data = config.product_coin
            for key in p_data.keys():
                okx_data(key.upper(), '1min', '1m')
    
        # 5分钟
        if (oktime % 120 == 0 or oktime == statime) and type == '2':
            time.sleep(5)
            p_data = config.product_coin
            for key in p_data.keys():
                okx_data(key.upper(), '5min', '5m')
    
        # 30分钟
        if (oktime % 300 == 0 or oktime == statime) and type == '3':
            time.sleep(5)
            p_data = config.product_coin
            for key in p_data.keys():
                okx_data(key.upper(), '30min', '30m')
    
        # 60分钟
        if (oktime % 300 == 0 or oktime == statime) and type == '4':
            time.sleep(5)
            p_data = config.product_coin
            for key in p_data.keys():
                okx_data(key.upper(), '1hour', '1H')
    
        # 1天
        if (oktime % 300 == 0 or oktime == statime) and type == '5':
            time.sleep(5)
            p_data = config.product_coin
            for key in p_data.keys():
                okx_data(key.upper(), '1day', '1D')

        # 10分钟脚本
        if oktime - statime > 24 * 60 * 60:
            break
        # 延迟1s
        time.sleep(0.5)
        # 跑脚本任务
        oktime = int(time.time())


if __name__ == '__main__':

    # while True:
        
    # okx_data('XMR', '1min', '1m')
    #     # 延迟1s
    #     time.sleep(5)
        
    # exit()
    
    
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
