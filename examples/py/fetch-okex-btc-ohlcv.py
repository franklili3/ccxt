# -*- coding: utf-8 -*-

import os
import sys
import time
import pandas as pd
# ------------------------------------------------------------------------------

root = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
sys.path.append(root + '/python')

# ------------------------------------------------------------------------------

import ccxt  # noqa: E402

# ------------------------------------------------------------------------------
symbol = 'BTC/USDT'
exchange = ccxt.okex()
#exchange.load_markets()
#for symbol in exchange.markets:
#    market = exchange.markets[symbol]
#    if market['future']:
#        print('----------------------------------------------------')
        #print(symbol, exchange.fetchTicker(symbol))
timeframe = '1m'
from_datetime = '2017-01-01 00:00:00'

from_timestamp = exchange.parse8601(from_datetime)
ohlcv = exchange.fetch_ohlcv(symbol, timeframe, from_timestamp)
first = ohlcv[0][0]
last = ohlcv[-1][0]
print('First candle datetime', exchange.iso8601(first))
print(len(ohlcv), 'Last candle datetime', exchange.iso8601(last))
time.sleep(exchange.rateLimit / 1000)
data_df = pd.DataFrame(ohlcv)
data_df.to_csv('okex_' + symbol + '_' + timeframe + '_' + from_datetime[0:10] + '.csv')