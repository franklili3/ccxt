<?php

namespace ccxt;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception as Exception; // a common import

class independentreserve extends Exchange {

    public function describe () {
        return array_replace_recursive (parent::describe (), array (
            'id' => 'independentreserve',
            'name' => 'Independent Reserve',
            'countries' => array ( 'AU', 'NZ' ), // Australia, New Zealand
            'rateLimit' => 1000,
            'has' => array (
                'CORS' => false,
            ),
            'urls' => array (
                'logo' => 'https://user-images.githubusercontent.com/1294454/30521662-cf3f477c-9bcb-11e7-89bc-d1ac85012eda.jpg',
                'api' => array (
                    'public' => 'https://api.independentreserve.com/Public',
                    'private' => 'https://api.independentreserve.com/Private',
                ),
                'www' => 'https://www.independentreserve.com',
                'doc' => 'https://www.independentreserve.com/API',
            ),
            'api' => array (
                'public' => array (
                    'get' => array (
                        'GetValidPrimaryCurrencyCodes',
                        'GetValidSecondaryCurrencyCodes',
                        'GetValidLimitOrderTypes',
                        'GetValidMarketOrderTypes',
                        'GetValidOrderTypes',
                        'GetValidTransactionTypes',
                        'GetMarketSummary',
                        'GetOrderBook',
                        'GetAllOrders',
                        'GetTradeHistorySummary',
                        'GetRecentTrades',
                        'GetFxRates',
                    ),
                ),
                'private' => array (
                    'post' => array (
                        'PlaceLimitOrder',
                        'PlaceMarketOrder',
                        'CancelOrder',
                        'GetOpenOrders',
                        'GetClosedOrders',
                        'GetClosedFilledOrders',
                        'GetOrderDetails',
                        'GetAccounts',
                        'GetTransactions',
                        'GetDigitalCurrencyDepositAddress',
                        'GetDigitalCurrencyDepositAddresses',
                        'SynchDigitalCurrencyDepositAddressWithBlockchain',
                        'WithdrawDigitalCurrency',
                        'RequestFiatWithdrawal',
                        'GetTrades',
                        'GetBrokerageFees',
                    ),
                ),
            ),
            'fees' => array (
                'trading' => array (
                    'taker' => 0.5 / 100,
                    'maker' => 0.5 / 100,
                    'percentage' => true,
                    'tierBased' => false,
                ),
            ),
        ));
    }

    public function fetch_markets ($params = array ()) {
        $baseCurrencies = $this->publicGetGetValidPrimaryCurrencyCodes ($params);
        $quoteCurrencies = $this->publicGetGetValidSecondaryCurrencyCodes ($params);
        $result = array();
        for ($i = 0; $i < count ($baseCurrencies); $i++) {
            $baseId = $baseCurrencies[$i];
            $baseIdUppercase = strtoupper($baseId);
            $base = $this->common_currency_code($baseIdUppercase);
            for ($j = 0; $j < count ($quoteCurrencies); $j++) {
                $quoteId = $quoteCurrencies[$j];
                $quoteIdUppercase = strtoupper($quoteId);
                $quote = $this->common_currency_code($quoteIdUppercase);
                $id = $baseId . '/' . $quoteId;
                $symbol = $base . '/' . $quote;
                $result[] = array (
                    'id' => $id,
                    'symbol' => $symbol,
                    'base' => $base,
                    'quote' => $quote,
                    'baseId' => $baseId,
                    'quoteId' => $quoteId,
                    'info' => $id,
                );
            }
        }
        return $result;
    }

    public function fetch_balance ($params = array ()) {
        $this->load_markets();
        $balances = $this->privatePostGetAccounts ($params);
        $result = array( 'info' => $balances );
        for ($i = 0; $i < count ($balances); $i++) {
            $balance = $balances[$i];
            $currencyId = $this->safe_string($balance, 'CurrencyCode');
            $code = $currencyId;
            if (is_array($this->currencies_by_id) && array_key_exists($currencyId, $this->currencies_by_id)) {
                $code = $this->currencies_by_id[$currencyId]['code'];
            } else {
                $code = $this->common_currency_code(strtoupper($currencyId));
            }
            $account = $this->account ();
            $account['free'] = $this->safe_float($balance, 'AvailableBalance');
            $account['total'] = $this->safe_float($balance, 'TotalBalance');
            $result[$code] = $account;
        }
        return $this->parse_balance($result);
    }

    public function fetch_order_book ($symbol, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $request = array (
            'primaryCurrencyCode' => $market['baseId'],
            'secondaryCurrencyCode' => $market['quoteId'],
        );
        $response = $this->publicGetGetOrderBook (array_merge ($request, $params));
        $timestamp = $this->parse8601 ($this->safe_string($response, 'CreatedTimestampUtc'));
        return $this->parse_order_book($response, $timestamp, 'BuyOrders', 'SellOrders', 'Price', 'Volume');
    }

    public function parse_ticker ($ticker, $market = null) {
        $timestamp = $this->parse8601 ($this->safe_string($ticker, 'CreatedTimestampUtc'));
        $symbol = null;
        if ($market) {
            $symbol = $market['symbol'];
        }
        $last = $this->safe_float($ticker, 'LastPrice');
        return array (
            'symbol' => $symbol,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'high' => $this->safe_float($ticker, 'DayHighestPrice'),
            'low' => $this->safe_float($ticker, 'DayLowestPrice'),
            'bid' => $this->safe_float($ticker, 'CurrentHighestBidPrice'),
            'bidVolume' => null,
            'ask' => $this->safe_float($ticker, 'CurrentLowestOfferPrice'),
            'askVolume' => null,
            'vwap' => null,
            'open' => null,
            'close' => $last,
            'last' => $last,
            'previousClose' => null,
            'change' => null,
            'percentage' => null,
            'average' => $this->safe_float($ticker, 'DayAvgPrice'),
            'baseVolume' => $this->safe_float($ticker, 'DayVolumeXbtInSecondaryCurrrency'),
            'quoteVolume' => null,
            'info' => $ticker,
        );
    }

    public function fetch_ticker ($symbol, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $request = array (
            'primaryCurrencyCode' => $market['baseId'],
            'secondaryCurrencyCode' => $market['quoteId'],
        );
        $response = $this->publicGetGetMarketSummary (array_merge ($request, $params));
        return $this->parse_ticker($response, $market);
    }

    public function parse_order ($order, $market = null) {
        $symbol = null;
        if ($market === null) {
            $symbol = $market['symbol'];
        } else {
            $market = $this->find_market($order['PrimaryCurrencyCode'] . '/' . $order['SecondaryCurrencyCode']);
        }
        $orderType = $this->safe_value($order, 'Type');
        if (mb_strpos($orderType, 'Market') !== false) {
            $orderType = 'market';
        } else if (mb_strpos($orderType, 'Limit') !== false) {
            $orderType = 'limit';
        }
        $side = null;
        if (mb_strpos($orderType, 'Bid') !== false) {
            $side = 'buy';
        } else if (mb_strpos($orderType, 'Offer') !== false) {
            $side = 'sell';
        }
        $timestamp = $this->parse8601 ($order['CreatedTimestampUtc']);
        $amount = $this->safe_float($order, 'VolumeOrdered');
        if ($amount === null) {
            $amount = $this->safe_float($order, 'Volume');
        }
        $filled = $this->safe_float($order, 'VolumeFilled');
        $remaining = null;
        $feeRate = $this->safe_float($order, 'FeePercent');
        $feeCost = null;
        if ($amount !== null) {
            if ($filled !== null) {
                $remaining = $amount - $filled;
                if ($feeRate !== null) {
                    $feeCost = $feeRate * $filled;
                }
            }
        }
        $feeCurrency = null;
        if ($market !== null) {
            $symbol = $market['symbol'];
            $feeCurrency = $market['base'];
        }
        $fee = array (
            'rate' => $feeRate,
            'cost' => $feeCost,
            'currency' => $feeCurrency,
        );
        $id = $this->safe_string($order, 'OrderGuid');
        $status = $this->parse_order_status($this->safe_string($order, 'Status'));
        $cost = $this->safe_float($order, 'Value');
        $average = $this->safe_float($order, 'AvgPrice');
        $price = $this->safe_float($order, 'Price', $average);
        return array (
            'info' => $order,
            'id' => $id,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'lastTradeTimestamp' => null,
            'symbol' => $symbol,
            'type' => $orderType,
            'side' => $side,
            'price' => $price,
            'cost' => $cost,
            'average' => $average,
            'amount' => $amount,
            'filled' => $filled,
            'remaining' => $remaining,
            'status' => $status,
            'fee' => $fee,
        );
    }

    public function parse_order_status ($status) {
        $statuses = array (
            'Open' => 'open',
            'PartiallyFilled' => 'open',
            'Filled' => 'closed',
            'PartiallyFilledAndCancelled' => 'canceled',
            'Cancelled' => 'canceled',
            'PartiallyFilledAndExpired' => 'canceled',
            'Expired' => 'canceled',
        );
        return $this->safe_string($statuses, $status, $status);
    }

    public function fetch_order ($id, $symbol = null, $params = array ()) {
        $this->load_markets();
        $response = $this->privatePostGetOrderDetails (array_merge (array (
            'orderGuid' => $id,
        ), $params));
        $market = null;
        if ($symbol !== null) {
            $market = $this->market ($symbol);
        }
        return $this->parse_order($response, $market);
    }

    public function fetch_my_trades ($symbol = null, $since = null, $limit = 50, $params = array ()) {
        $this->load_markets();
        $pageIndex = $this->safe_integer($params, 'pageIndex', 1);
        if ($limit === null) {
            $limit = 50;
        }
        $request = $this->ordered (array (
            'pageIndex' => $pageIndex,
            'pageSize' => $limit,
        ));
        $response = $this->privatePostGetTrades (array_merge ($request, $params));
        $market = null;
        if ($symbol !== null) {
            $market = $this->market ($symbol);
        }
        return $this->parse_trades($response['Data'], $market, $since, $limit);
    }

    public function parse_trade ($trade, $market = null) {
        $timestamp = $this->parse8601 ($trade['TradeTimestampUtc']);
        $id = $this->safe_string($trade, 'TradeGuid');
        $orderId = $this->safe_string($trade, 'OrderGuid');
        $price = $this->safe_float_2($trade, 'Price', 'SecondaryCurrencyTradePrice');
        $amount = $this->safe_float_2($trade, 'VolumeTraded', 'PrimaryCurrencyAmount');
        $cost = null;
        if ($price !== null) {
            if ($amount !== null) {
                $cost = $price * $amount;
            }
        }
        $symbol = null;
        if ($market !== null) {
            $symbol = $market['symbol'];
        }
        $side = $this->safe_string($trade, 'OrderType');
        if ($side !== null) {
            if (mb_strpos($side, 'Bid') !== false) {
                $side = 'buy';
            } else if (mb_strpos($side, 'Offer') !== false) {
                $side = 'sell';
            }
        }
        return array (
            'id' => $id,
            'info' => $trade,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'symbol' => $symbol,
            'order' => $orderId,
            'type' => null,
            'side' => $side,
            'takerOrMaker' => null,
            'price' => $price,
            'amount' => $amount,
            'cost' => $cost,
            'fee' => null,
        );
    }

    public function fetch_trades ($symbol, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $request = array (
            'primaryCurrencyCode' => $market['baseId'],
            'secondaryCurrencyCode' => $market['quoteId'],
            'numberOfRecentTradesToRetrieve' => 50, // max = 50
        );
        $response = $this->publicGetGetRecentTrades (array_merge ($request, $params));
        return $this->parse_trades($response['Trades'], $market, $since, $limit);
    }

    public function create_order ($symbol, $type, $side, $amount, $price = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $capitalizedOrderType = $this->capitalize ($type);
        $method = 'privatePostPlace' . $capitalizedOrderType . 'Order';
        $orderType = $capitalizedOrderType;
        $orderType .= ($side === 'sell') ? 'Offer' : 'Bid';
        $request = $this->ordered (array (
            'primaryCurrencyCode' => $market['baseId'],
            'secondaryCurrencyCode' => $market['quoteId'],
            'orderType' => $orderType,
        ));
        if ($type === 'limit') {
            $request['price'] = $price;
        }
        $request['volume'] = $amount;
        $response = $this->$method (array_merge ($request, $params));
        return array (
            'info' => $response,
            'id' => $response['OrderGuid'],
        );
    }

    public function cancel_order ($id, $symbol = null, $params = array ()) {
        $this->load_markets();
        $request = array (
            'orderGuid' => $id,
        );
        return $this->privatePostCancelOrder (array_merge ($request, $params));
    }

    public function sign ($path, $api = 'public', $method = 'GET', $params = array (), $headers = null, $body = null) {
        $url = $this->urls['api'][$api] . '/' . $path;
        if ($api === 'public') {
            if ($params) {
                $url .= '?' . $this->urlencode ($params);
            }
        } else {
            $this->check_required_credentials();
            $nonce = $this->nonce ();
            $auth = array (
                $url,
                'apiKey=' . $this->apiKey,
                'nonce=' . (string) $nonce,
            );
            $keys = is_array($params) ? array_keys($params) : array();
            for ($i = 0; $i < count ($keys); $i++) {
                $key = $keys[$i];
                $value = (string) $params[$key];
                $auth[] = $key . '=' . $value;
            }
            $message = implode(',', $auth);
            $signature = $this->hmac ($this->encode ($message), $this->encode ($this->secret));
            $query = $this->ordered (array());
            $query['apiKey'] = $this->apiKey;
            $query['nonce'] = $nonce;
            $query['signature'] = strtoupper($signature);
            for ($i = 0; $i < count ($keys); $i++) {
                $key = $keys[$i];
                $query[$key] = $params[$key];
            }
            $body = $this->json ($query);
            $headers = array( 'Content-Type' => 'application/json' );
        }
        return array( 'url' => $url, 'method' => $method, 'body' => $body, 'headers' => $headers );
    }
}
