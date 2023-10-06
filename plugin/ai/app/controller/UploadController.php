<?php

namespace plugin\ai\app\controller;

use Exception;
use Intervention\Image\ImageManagerStatic as Image;
use support\Request;
use support\Response;

class UploadController extends Base
{

    /**
     * 不需要登录的方法
     *
     * @var string[]
     */
    protected $noNeedLogin = ['avatar', 'image'];

    /**
     * 头像设置
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function avatar(Request $request): Response
    {
        $file = $request->file();
        $file = $file ? current($file) : false;
        if ($file && $file->isValid()) {
            $ext = strtolower($file->getUploadExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'gif', 'png'])) {
                return $this->json(2, '仅支持 jpg jpeg gif png格式');
            }
            $image = Image::make($file);
            $maxWidth = $maxHeight = 800;
            $image->resize($maxWidth, $maxHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $relativePath = 'upload/avatar';
            $realPath = base_path("/plugin/ai/public/$relativePath");
            if (!is_dir($realPath)) {
                mkdir($realPath, 0777, true);
            }
            $name = base_convert(time() * 1000 + random_int(1000, 9999), 10, 36) . ".$ext";
            $url = "/app/ai/$relativePath/$name";
            $path = "$realPath/$name";
            $image->save($path);
            return $this->json(0, 'upload success', ['url' => $url]);
        }
        return $this->json(1, 'file not found');
    }

    /**
     * 上传图片
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function image(Request $request): Response
    {
        $file = $request->file();
        $file = $file ? current($file) : false;
        if ($file && $file->isValid()) {
            $ext = strtolower($file->getUploadExtension());
            if (!in_array($ext, ['jpg', 'jpeg','png'])) {
                return $this->json(2, '仅支持 jpg jpeg png格式');
            }
            $image = Image::make($file);
            $width = $image->width();
            $height = $image->height();
            $size = min($width, $height);
            $image->crop($size, $size)->resize(800, 800);
            $relativePath = 'upload/images/' . date('Ym');
            $realPath = base_path("/plugin/ai/public/$relativePath");
            if (!is_dir($realPath)) {
                mkdir($realPath, 0777, true);
            }
            $name = base_convert(time() * 1000 + random_int(1000, 9999), 10, 36) . ".$ext";
            $url = "/app/ai/$relativePath/$name";
            $path = "$realPath/$name";
            $image->save($path);
            return $this->json(0, 'upload success', ['url' => $url]);
        }
        return $this->json(1, 'file not found');
    }

}
