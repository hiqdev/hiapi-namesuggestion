<?php
/**
 * hiAPI NameSuggestion.com plugin
 *
 * @link      https://github.com/hiqdev/hiapi-namesuggestion
 * @package   hiapi-namesuggestion
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\namesuggestion;

use err;
use arr;

/**
 * NameSuggestion.com tool.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class NameSuggestionTool extends \hiapi\components\AbstractTool
{
    protected $response_meta_info;

    public function domainGetSuggestions($row)
    {
        try {
            return $this->execute('suggest', arr::merge($row, $this->prepareDefaults($row)));
        } catch (\Exception $e) {
            return err::set($row, $e->getMessage());
        }
    }

    public function domainsGetSuggestions($jrow)
    {
        try {
            foreach ($jrow['names'] as $id => $domain) {
                $res[$id] = $this->execute('suggest', arr::merge([
                    'name'  => $domain,
                ], $this->prepareDefaults($jrow)));
            }
        } catch (\Exception $e) {
            return err::set($rows, $e->getMessage());
        }

        return $res;
    }

    public function domainsCheck ($jrow)
    {
        return $this->execute('bulk-search', arr::merge($jrow, $this->prepareDefaults($jrow)));
    }

    private function prepareDefaults($data)
    {
        static $tlds;
        if ($tlds === null) {
            $tlds = arr::cjoin($data['tlds']);
        }

        return [
            'tlds' => $tlds,
            'use-numbers' => false,
            'use-idns' => false,
            'use-dashes' => true,
            'max-results' => 100,
        ];
    }

    private function execute($method, $data)
    {
        unset($data['access_id']);
        static $deep;
        if ($deep === null) {
            $deep = 0;
        }

        $pageData = $this->get($method,$data);
        switch ($pageData['http_status']) {
            case 200:
                return json_decode($pageData['response'], true);
            case 429:
                if ($deep < 10) {
                    usleep(abs($pageData['response_meta_info']['X-RateLimit-TimeMs'])*1000);
                    $deep++;
                    return $this->execute($method, $data);
                }
                throw new \Exception('requests limit exceeded');
            case 401:
                throw new \Exception('Invalid API key');
        }

        return [];
    }

    /**
     *
     * main function that makes HTTPS call to rest service for single domain and retrieves the results
     *
    **/
    private function get($method, $data)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_USERAGENT       => 'curl/0.00 (php 5.x; U; en)',
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_SSL_VERIFYPEER  => FALSE,
            CURLOPT_SSL_VERIFYHOST  => 2,
            CURLOPT_URL => "{$this->data['url']}{$method}?" . http_build_query($data),
            CURLOPT_HTTPHEADER      => [
                'Accept: application/json',
                'X-NAMESUGGESTION-APIKEY:' . $this->data['password'],
                "Expect:",
            ],
            CURLOPT_HEADER          => 0,
            CURLINFO_HEADER_OUT     => true,
            CURLOPT_HEADERFUNCTION  => [&$this, 'readHeader'],
            CURLOPT_BUFFERSIZE      => 64000,
        ]);
        $searchResponse['response'] = curl_exec($ch);
        $searchResponse['http_status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $searchResponse['response_meta_info'] =  $this->response_meta_info;
        if ($searchResponse['http_status'] !== 200){
            $message = json_decode($searchResponse['response'], true);
            throw new \Exception('error during request: '. $message['message']);
        }
        curl_close($ch);
        return $searchResponse;
    }

    /**
     * CURL callback function for reading and processing headers
     * Override this for your needs
     *
     * @param object $ch
     * @param string $header
     * @return integer
    */
    public function readHeader($ch, $header) {
        //extracting example data: filename from header field Content-Disposition
        $rateLimit = $this->extractCustomHeader('X-RateLimit-Limit:', '\n', $header);
        if ($rateLimit) {
            $this->response_meta_info['X-RateLimit-Limit'] = trim($rateLimit);
        }


        $timeMs = $this->extractCustomHeader('X-RateLimit-TimeMs:', '\n', $header);
        if ($timeMs) {
          $this->response_meta_info['X-RateLimit-TimeMs'] = trim($timeMs);
        }

        $remaining = $this->extractCustomHeader('X-RateLimit-Remaining:', '\n', $header);
        if ($remaining) {
          $this->response_meta_info['X-RateLimit-Remaining'] = trim($remaining);
        }

        $resetMs = $this->extractCustomHeader('X-RateLimit-ResetMs:', '\n', $header);
        if ($resetMs) {
          $this->response_meta_info['X-RateLimit-ResetMs'] = trim($resetMs);
        }

        return strlen($header);
    }

    public function extractCustomHeader($start,$end,$header) {
        $pattern = '/'. $start .'(.*?)'. $end .'/';
        if (preg_match($pattern, $header, $result)) {
            return $result[1];
        } else {
            return false;
        }
    }
}
