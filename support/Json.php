<?php
/**
 * @author charles
 * @created 2023/10/31 17:10
 */

namespace support;
class Json
{


    public function make(int $status, string $msg, ?array $data = null, ?array $replace = []): Response
    {
        $res = compact('status', 'msg');

        if (!is_null($data))
            $res['data'] = $data;

        if (is_numeric($res['msg'])) {
            $res['status'] = $res['msg'];
          //  $res['msg'] = getLang($res['msg'], $replace);多语言时候

        }
        return json($res);

    }

    /**
     * @param $msg
     * @param array|null $data
     * @param array|null $replace
     * @return Response
     */
    public function success($msg = 'success', ?array $data = null, ?array $replace = []): Response
    {
        if (is_array($msg) || is_object($msg)) {
            $data = $msg;
            $msg = 'success';
        }

        return $this->make(0, $msg, $data, $replace);
    }

    /**
     * @param $msg
     * @param array|null $data
     * @param array|null $replace
     * @return Response
     */
    public function fail($msg = 'fail', ?array $data = null, ?array $replace = []): Response
    {
        if (is_array($msg) || is_object($msg)) {
            $data = $msg;
            $msg = 'fail';
        }

        return $this->make(200, $msg, $data, $replace);
    }

    public function status($status, $msg, $result = [])
    {
        $status = strtoupper($status);
        if (is_array($msg)) {
            $result = $msg;
            $msg = 'success';
        }
        return $this->success($msg, compact('status', 'result'));
    }

}