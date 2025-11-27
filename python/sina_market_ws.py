
from websocket import create_connection
import ssl


if __name__ == "__main__":

    
    wss_url = "wss://w.sinajs.cn/wskt?list=fx_susdcny,fx_susdgbp,fx_susdeur,fx_susdhkd,fx_susdjpy,fx_susdkrw,fx_susdaud,fx_susdcad,fx_susdthb,fx_susdsgd,fx_susdtwd"
    wss = create_connection(wss_url, timeout=10,sslopt={"cert_reqs": ssl.CERT_NONE})
    # wss = create_connection(wss_url, timeout=10,sslopt={"cert_reqs": ssl.CERT_NONE})
    if wss.status == 101:
        print("connect ok")
        wss.send('')

        msg = wss.recv()

        print(msg)
