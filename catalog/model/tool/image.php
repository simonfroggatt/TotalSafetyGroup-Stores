<?php
class ModelToolImage extends Model
{



    public function getImage($filename)
    {
        if (USE_CDN) {
            $image = TSG_CDN_URL . $filename;
        } else {
            $image = 'image/' . $filename;
        }

        return $image;
    }

    public function resize_old($filename, $width, $height)
    {
        //$test1 = is_file(DIR_IMAGE . $filename);
       // $test2 = substr(str_replace('\\', '/', realpath(DIR_IMAGE . $filename)), 0, strlen(DIR_IMAGE));
       // $test3 = str_replace('\\', '/', DIR_IMAGE);
        //$cleaned_url = DIR_IMAGE. str_replace(DJANGO_DROP_DIR, '', $filename);
        //if (!is_file(DIR_IMAGE . $filename) || substr(str_replace('\\', '/', realpath(DIR_IMAGE . $filename)), 0, strlen(DIR_IMAGE)) != str_replace('\\', '/', DIR_IMAGE)) {
       /* if (!is_file(DIR_IMAGE . $filename)) {
            return TSG_CDN_URL. 'store/' . 'no_image.png';
        }
*/
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        //SSAN - cut in here
      /*  if ($extension == 'svg') {
            $image_new = 'image/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '.' . $extension;

            if ($this->request->server['HTTPS']) {
                return $this->config->get('config_ssl') . $image_new;
            } else {
                return $this->config->get('config_url') . $image_new;
            }

        }*/

        if(USE_CDN){
            $image = TSG_CDN_URL. $filename;
        } else {
            $image = 'image/'. $filename;
        }



        return $image;

        $image_old = $filename;
        $image_new = 'cache/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.' . $extension;

        if (!is_file(DIR_IMAGE . $image_new) || (filemtime(DIR_IMAGE . $image_old) > filemtime(DIR_IMAGE . $image_new))) {
            list($width_orig, $height_orig, $image_type) = getimagesize(DIR_IMAGE . $image_old);

            if (!in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF))) {
                return DIR_IMAGE . $image_old;
            }

            $path = '';

            $directories = explode('/', dirname($image_new));

            foreach ($directories as $directory) {
                $path = $path . '/' . $directory;

                if (!is_dir(DIR_IMAGE . $path)) {
                    @mkdir(DIR_IMAGE . $path, 0777);
                }
            }

            if ($width_orig != $width || $height_orig != $height) {
                $image = new Image(DIR_IMAGE . $image_old);
                $image->resize($width, $height);
                $image->save(DIR_IMAGE . $image_new);
            } else {
                copy(DIR_IMAGE . $image_old, DIR_IMAGE . $image_new);
            }
        }

        $image_new = str_replace(' ', '%20', $image_new);  // fix bug when attach image on email (gmail.com). it is automatic changing space " " to +

        if ($this->request->server['HTTPS']) {
            return $this->config->get('config_ssl') . 'image/' . $image_new;
        } else {
            return $this->config->get('config_url') . 'image/' . $image_new;
        }
    }

    public function resize_svg($filename)
    {

        if (!is_file(DIR_IMAGE . $filename)) {
            return;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        //SSAN - cut in here
        if ($extension == 'svg') {
            $image_new = 'cache/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-boxed.' . $extension;

            //load svg into memory
            $svg_file = file_get_contents(DIR_IMAGE . $filename);



            $svgTemplate = new SimpleXMLElement($svg_file);
            //add a box around the svg

            $bl_saved = $svgTemplate->asXML(DIR_IMAGE . $image_new);

            if ($this->request->server['HTTPS']) {
                return $this->config->get('config_ssl') . 'image/' . $image_new;
            } else {
                return $this->config->get('config_url') . 'image/' . $image_new;
            }

        }
    }

    public function category_list_svg($filename)
    {

        if (!is_file(DIR_IMAGE . $filename)) {
            return;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        //SSAN - cut in here
        if ($extension == 'svg') {
            $path_parts = pathinfo($filename);

            $image_new =  $path_parts['dirname']. '/svgboxed/'.$path_parts['filename'] . '-boxed.' . $path_parts['extension'];
            if (!is_file($this->config->get('config_ssl')  . 'image/'. $image_new)) {
                return;
            }

            //load svg into memory
            $svg_file = file_get_contents(DIR_IMAGE . $filename);

/*

            $svgTemplate = new SimpleXMLElement($svg_file);
            //add a box around the svg

            $bl_saved = $svgTemplate->asXML(DIR_IMAGE . $image_new);*/

            if ($this->request->server['HTTPS']) {
                return $this->config->get('config_ssl')  . 'image/'. $image_new;
            } else {
                return $this->config->get('config_url')  . 'image/'. $image_new;
            }

        }
    }


}
