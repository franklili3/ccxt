# -*- coding: utf-8 -*-

import os
import sys
import time
import pandas as pd
# -----------------------------------------------------------------------------

root = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
sys.path.append(root + '/python')

# -----------------------------------------------------------------------------

import ccxt  # noqa: E402

# -----------------------------------------------------------------------------
# common constants

msec = 1000
minute = 60 * msec
hold = 30

# -----------------------------------------------------------------------------

exchange = ccxt.bitz({
    'rateLimit': 3000,
    'enableRateLimit': True,
    # 'verbose': True,
})

# -----------------------------------------------------------------------------

from_datetime = '2017-01-01 00:00:00'

from_timestamp = exchange.parse8601(from_datetime)

# -----------------------------------------------------------------------------

now = exchange.milliseconds()

# -----------------------------------------------------------------------------

data = []

while from_timestamp < now:

    try:

        print('Fetching candles starting from', exchange.iso8601(from_timestamp))
        ohlcvs = exchange.fetch_ohlcv('BTC/USDT', '1m', from_timestamp, limit=3000)
        print('Fetched', len(ohlcvs), 'candles')
        first = ohlcvs[0][0]
        last = ohlcvs[-1][0]
        print('First candle datetime', exchange.iso8601(first))
        print('Last candle datetime', exchange.iso8601(last))
        from_timestamp += len(ohlcvs) * minute
        data += ohlcvs

    except (ccxt.ExchangeError, ccxt.AuthenticationError, ccxt.ExchangeNotAvailable, ccxt.RequestTimeout) as error:

        print('Got an error', type(error).__name__, error.args, ', retrying in', hold, 'seconds...')
        time.sleep(hold)
data_df = pd.DataFrame(data)
data_df.to_csv('bitz_BTC/USDT_1m_' + from_datetime[0:10] + '.csv')
