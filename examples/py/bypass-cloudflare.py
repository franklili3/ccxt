# -*- coding: utf-8 -*-

import cfscrape
import os
import sys
import csv

root = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
sys.path.append(root + '/python')

import ccxt  # noqa: E402


def print_supported_exchanges():
    print('Supported exchanges:')
    print(', '.join(ccxt.exchanges))

def retry_fetch_ohlcv(exchange, max_retries, symbol, timeframe, since, limit):
    num_retries = 0
    success = False
    while not success:
        try:
            num_retries += 1
            ohlcv = exchange.fetch_ohlcv(symbol, timeframe, since, limit)
            print('Fetched', len(ohlcv), symbol, 'candles from', exchange.iso8601 (ohlcv[0][0]), 'to', exchange.iso8601 (ohlcv[-1][0]))
            success = True
            return ohlcv
        except Exception:
            if num_retries > max_retries:
                raise Exception('Failed to fetch', timeframe, symbol, 'OHLCV in', max_retries, 'attempts')


def scrape_ohlcv(exchange, max_retries, symbol, timeframe, since, limit):
    earliest_timestamp = exchange.milliseconds()
    timeframe_duration_in_seconds = exchange.parse_timeframe(timeframe)
    timeframe_duration_in_ms = timeframe_duration_in_seconds * 1000
    timedelta = limit * timeframe_duration_in_ms
    all_ohlcv = []
    while True:
        fetch_since = earliest_timestamp - timedelta
        ohlcv = retry_fetch_ohlcv(exchange, max_retries, symbol, timeframe, fetch_since, limit)
        # if we have reached the beginning of history
        if ohlcv[0][0] >= earliest_timestamp:
            break
        earliest_timestamp = ohlcv[0][0]
        first = ohlcv[0][0]
        last = ohlcv[-1][0]
        print('First candle datetime', exchange.iso8601(first))
        print('Last candle datetime', exchange.iso8601(last))
        all_ohlcv = ohlcv + all_ohlcv
        print(len(all_ohlcv), 'candles in total from', exchange.iso8601(all_ohlcv[0][0]), 'to', exchange.iso8601(all_ohlcv[-1][0]))
        # if we have reached the checkpoint
        if fetch_since < since:
            break
    return all_ohlcv


def write_to_csv(filename, data):
    with open(filename, mode='w') as output_file:
        csv_writer = csv.writer(output_file, delimiter=',', quotechar='"', quoting=csv.QUOTE_MINIMAL)
        csv_writer.writerows(data)


def scrape_candles_to_csv(filename, id, max_retries, symbol, timeframe, since, limit, exchange):
    # instantiate the exchange by id
    #exchange = getattr(ccxt, exchange_id)({
    #    'enableRateLimit': True,  # required by the Manual
    #})
    #exchange = getattr(ccxt, id)({
            #'timeout': 20000,
            #'session': cfscrape.create_scraper(),
    #})
    # convert since from string to milliseconds integer if needed
    if isinstance(since, str):
        since = exchange.parse8601(since)
    # preload all markets from the exchange
    #exchange.load_markets()
    # fetch all candles
    ohlcv = scrape_ohlcv(exchange, max_retries, symbol, timeframe, since, limit)
    # save them to csv file
    write_to_csv(filename, ohlcv)
    print('Saved', len(ohlcv), 'candles from', exchange.iso8601(ohlcv[0][0]), 'to', exchange.iso8601(ohlcv[-1][0]), 'to', filename)

try:

    id = sys.argv[1]  # get exchange id from command line arguments

    # check if the exchange is supported by ccxt
    exchange_found = id in ccxt.exchanges

    if exchange_found:

        print('Instantiating ' + id + ' exchange')

        # instantiate the exchange by id
        exchange = getattr(ccxt, id)({
            'timeout': 20000,
            'session': cfscrape.create_scraper(),
            'enableRateLimit': True,  # required by the Manual
        })

        try:

            # load all markets from the exchange
            markets = exchange.load_markets()

            # output a list of all market symbols
            print(id + ' has ' + str(len(exchange.symbols)) + ' symbols: ' + ', '.join(exchange.symbols))
            print('Succeeded.')
            scrape_candles_to_csv(id + '_2016-01-01.csv', id, 100, 'BTC/USDT', '1m', '2016-01-01T00:00:00Z', 1000, exchange)

        except ccxt.BaseError as e:

            print(type(e).__name__, str(e))
            print('Failed.')

    else:

        print('Exchange ' + id + ' not found')
        print_supported_exchanges()

except Exception as e:
    print('[' + type(e).__name__ + ']', str(e))
    print('Usage: python ' + sys.argv[0] + ' id')
    print_supported_exchanges()
