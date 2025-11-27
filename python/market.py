import os, sys

object_path = os.path.join(os.path.abspath(os.path.dirname(os.path.dirname(__file__))))
sys.path.append(object_path)
import json
# from requests_html import HTMLSession

# session = HTMLSession()
import time
import random
import requests
import config


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
    url = 'https://vip.stock.finance.sina.com.cn/forex/api/jsonp.php/var _' + symbol + '_' + t_type + '_' + timestr + '=/NewForexService.getMinKline?symbol=' + symbol + '&scale=' + t_type + '&datalen=10'
    # url = 'https://api-q.fx678img.com/histories.php?symbol=' + symbol + '&limit=1000&resolution=' + resolution + '&codeType=' + codeType

    # 请求头
    headers = {
        "Referer": "https://finance.sina.com.cn/money/forex/hq/USDJPY.shtml",
    }

    # 发起请求
    response = requests.post(url, headers=headers)
    
    

# 新浪数据
def sina(symbol, sytype):
    # timestr = str(int(time.time() * 1000))
    
    timestr = str(int(time.time() * 1000))
    t_type = '120'
    
    
    url = 'https://vip.stock.finance.sina.com.cn/forex/api/jsonp.php/var _' + symbol + '_' + t_type + '_' + timestr + '=/NewForexService.getMinKline?symbol=' + symbol + '&scale=' + t_type + '&datalen=12'
    
    # 请求头
    headers = {
        "Referer": "https://finance.sina.com.cn/money/forex/hq/USDJPY.shtml",
    }

    # 发起请求
    response = requests.post(url, headers=headers)
    # 请求成功
    if response.status_code == 200:

        print('行情 ' + ' ' + sytype + ' sina 数据获取成功！')

        # var _fx_susdjpy_1_1681962420688=
        jsonstr = response.text.replace("var _" + symbol + '_' + t_type + '_' + timestr + "=(", "")
        jsonstr = jsonstr.replace(");", "")
        jsonstr = jsonstr.replace("/*<script>location.href='//sina.com';</script>*/", "")
        jsonstr = jsonstr.replace("\n", "")
        # print("var_XAU_" + t_type + "_" + timestr + "=(")
        # print(jsonstr[0:130])
        # 数据转json格式
        redata = json.loads(jsonstr)
        
        re_dic = {'h':'0','l':'0','o':redata[0]['c'],'c':redata[-1]['c']}
        for o_data in redata:
            if float(o_data['h']) > float(re_dic['h']):
                re_dic['h'] = o_data['h']
            
            if float(o_data['l']) < float(re_dic['l']):
                re_dic['l'] = o_data['l']
        
        
        print(re_dic)
        # 交易量
        volume = random.randint(20000, 80000)
        
        result = {
            "price_high": re_dic['h'],
            "price_low": re_dic['l'],
            "price": modify_last_decimal_digit("%.4f" % float(re_dic['c'])),
            "vol": volume,
            "open_price": re_dic['o'],
            "price_zf": re_dic['h'],
            "price_pre": re_dic['c'],
        }
        
        cache = {}
        cache[sytype] = result
        json_data = config.r.get("updateProduct")
        if json_data != None:
            list = json.loads(json_data)
            # print(list)
            for j_key in list.keys():
                list[j_key]['price'] = modify_last_decimal_digit("%.4f" % float(list[j_key]['price']))
                # TODO: write code...
            if sytype in list:
                print(list[sytype])
                list[sytype] = result
                cache = list
            # print(list[sytype])

        config.r.set("updateProduct", json.dumps(cache))
    else:
        print('失败 ' + url)
        print(response)
    
# 新浪数据
def sinaold(symbol, sytype):
    timestr = str(int(time.time() * 1000))
    
    # 新浪 行情 接口
    # https://hq.sinajs.cn/etag.php?_=1681972384088&list=fx_susdjpy

    # 接口地址https://hq.sinajs.cn/etag.php?_=1730120983859&list=fx_susdcny
    url = 'https://hq.sinajs.cn/etag.php?_=' + timestr + '&list=' + symbol
    # url = 'https://hq.sinajs.cn/rn=1730121510829list=fx_seurjpy,fx_sgbpjpy,fx_seurgbp,fx_seurchf,fx_shkdusd,fx_seuraud,fx_seurcad,fx_sgbpaud,fx_sgbpcad,fx_schfjpy,fx_sgbpchf,fx_scadjpy,fx_saudjpy,fx_seurnzd,fx_sgbpnzd'
    请求头
    headers = {
        "If-None-Match": 'W/"ICiAUFnH40n"',
        "Referer": "https://finance.sina.com.cn/",
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:131.0) Gecko/20100101 Firefox/131.0",
    }

    # # 发起请求
    response = requests.get(url, headers=headers)
    # print(response)

    # 请求成功
    if response.status_code == 200:

        # 清理无用数据
        jsonstr = response.text.replace("var hq_str_" + symbol + "=\"", "")

        # print(jsonstr[0:30])

        # exit()

        # 分割字符串
        vi = jsonstr.split(',')

        # 定义容器
        k_dic = {}

        # 0时间,1当前价格,2不知道,昨3收,4 v,5今开,6最高,7最低,8当前价 JPY 日元
        # "0 当前,1 昨关,2 当前,3 卖价,4 最高价,5 最低价, 6 时间,7 昨关,8 开盘,0,0,0,2023-04-20,伦敦金（现货黄金）";
        result = {
            "code": sytype,
        }
        if sytype == 'GOLD':
            volume = random.randint(1000, 10000)
            result = {
                "price_high": vi[4],
                "price_low": vi[5],
                "price": vi[0],
                "vol": volume,
                "open_price": vi[8],
                "price_zf": vi[4],
                "price_pre": vi[0],
            }
        else:
            
            
            result = {
                "price_high": vi[6],
                "price_low": vi[7],
                "price": modify_last_decimal_digit("%g" % float(vi[1])),
                "vol": vi[4],
                "open_price": vi[5],
                "price_zf": vi[6],
                "price_pre": vi[1],
            }

        print(sytype + ' sina 数据获取成功 | ' + result['price'] + ' | ' + time.strftime("%Y-%m-%d %H:%M:%S",
                                                                                         time.localtime()))

        cache = {}
        cache[sytype] = result
        json_data = config.r.get("updateProduct")
        if json_data != None:
            list = json.loads(json_data)
            if sytype in list:
                print(list[sytype])
            list[sytype] = result
            cache = list
            print(list[sytype])

        config.r.set("updateProduct", json.dumps(cache))

        print("---------------------------------------------")
    else:
        print('失败 ' + url)
        print(response)

if __name__ == '__main__':

    statime = int(time.time())

    # sina('hf_XAU', 'GOLD')
    # exit()

    while True:
        # try:
        oktime = int(time.time())
        # print(oktime)

        p_data = config.product
        for key in p_data.keys():
            sina('fx_' + key, key)
            time.sleep(10)
            # sina('fx_susdjpy', 'JPY')
        # JPY

        time.sleep(5)

        # GOLD
        # sina('hf_XAU', 'GOLD')

        # time.sleep(5)

        # 10分钟脚本
        if oktime - statime > 10 * 60:
            break

    # except:
    #     print('======错误=======')
    #     time.sleep(60)
    #     pass
    exit()
