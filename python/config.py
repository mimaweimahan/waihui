import redis
import json


def getConfig(config):
    return config

def getStocks(count, cid):
    data = {}
    for num in range(0, count):
        num += 1
        if num >= 10:
            key = "j0" + str(num) + "_product_" + str(cid)
        else:
            key = "j00" + str(num) + "_product_" + str(cid)
        json_data = r.get(key)
        if json_data != None:
            list = json.loads(json_data)
            data.update(list)

    return data


def getRedis(key):
    data = {}
    json_data = r.get(key)
    if json_data != None:
        list = json.loads(json_data)
        data.update(list)
    return data



r = redis.Redis(
        host='127.0.0.1',
        port=6379,
        db=4,
        decode_responses=True)

# 服务器总台子数
count = 5

# 虚拟币
product_coin = getStocks(count,'1')

# 外汇
product = getStocks(count,'2')

# 贵金属
product_gold = getStocks(count,'3')

# 美股
product_stock = getStocks(count,'4')

# 期货
product_futures = getStocks(count,'5')



class WebsocketConfig:
    coin_list = [
        "gbpusd",
    ]
