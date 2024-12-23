<?php

namespace App\Http\Controllers\API\V1\Service;

use GuzzleHttp\Client;

class TryOnService
{
    protected $client;
    protected $apiKey;
    protected $apiEndpoint;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = "Qk5CIHEsXOkAzs3MR1lCKCayUPVF12U7Jnyn25Z039HY9MbXvFL4iv5hucymmOIEXvq9c+vF9ABLSya1WgebGfyv/HxVYWG+hY1UjC08lbvYz99HKLKxjs1vFLccFHABR8M0Ig75CVk3/+kpMiJaHxPLEdMdWo16MqniZGP8H3YNLaISmvm28ALLwS23Fhawwlu2S2zVoVtT/N5VbWMLjQT9N+QSXbkwCDmSraIXHDBvs56FGPoMtgI4qIwVl1e1mNDqxJZ2vAYhPCgdhx5CFf/1izi28R9b7zLTS3qt5tsBUancC7WDW2gQc5k7vTP8/Lo4x1qxVeDsrpRiCDgi8S0AGNLvKssbzvlBgchm3Q7pP8T7o0EsyUwTDW6zgkONfpEjIcMael4k1phMO637rzYZELbreWXvxZexg7cFs5r+qy6ftzKs7N77AAlqb7PAbqNfJtCslxiMHnyMKyJrvuCmTydgy3KLrBKX1XuphSMqOzdHd4lm5f8AF7kG+tpRC5AAMA04K2WE2xnO/F2WvoDo9EgshgpQjww3KqbiCFxJDo9LLi5kyQH5VUJFpcy9iqVJ3WvZJvA2RcmXTLeVokTZF143CnLUJcGIPY2Y7R0iNmDVp9evUztl51h/Ppg5Kq3OesT0JwsVnbJWTj/uDmb9";
        $this->apiEndpoint = "https://api.your-ai-provider.com/try-on";
        // dd( $this->apiKey,
        // $this->apiEndpoint);
    }

    public function tryOnClothes($userImageUrl, $outfitImageUrl)
    {
        // Gửi yêu cầu POST đến API của AI với hai URL ảnh
        $response = $this->client->post($this->apiEndpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'user_image_url' => $userImageUrl,     // URL ảnh người dùng
                'outfit_image_url' => $outfitImageUrl, // URL ảnh bộ đồ
            ],
            'timeout' => 60, // Tăng thời gian chờ lên 60 giây
            'connect_timeout' => 15, // Thời gian chờ kết nối là 15 giây
        ]);

        // Giải mã kết quả từ API
        return json_decode($response->getBody()->getContents(), true);
    }
}
