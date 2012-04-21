<?php

Class Wiz_Plugin_301 extends Wiz_Plugin_Abstract {

    /**
     * Generates a CSV-compatible SKU to URL mapping output.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function urlskumapAction($options) {
        Wiz::getMagento('');
        $output = '';
        $products = Mage::getModel('catalog/product')->getCollection();
        foreach ($products as $product) {
            if (($sku = trim($product->getSku())) == '')
                continue;
            $output .= trim($product->getSku()).','.$product->getProductUrl().PHP_EOL;
        }
        if (strpos($options[0], '.') !== FALSE) {
            file_put_contents($options[0], $output);
        }
        else {
            echo $output;
        }
    }

    /**
     * Outputs a list of category to URL mappings.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function urlcatmapAction($options) {
        Wiz::getMagento('');
        $output = '';

        $categories = Mage::getModel('catalog/category')->getCollection();

        foreach ($categories as $categoryModel) {
            $category = Mage::getModel('catalog/category')->load($categoryModel->getId());
            $breadcrumb = array();
            $data = array();

            $data[] = $category->getName();
            $data[] = $category->getUrl();

            $parentCategories = $category->getParentCategories();
            foreach ($parentCategories as $pc) {
                $breadcrumb[] = $pc->getName();
            }

            $data[] = '"'.implode("\t", $breadcrumb).'"';
            $output .= implode(', ', $data).PHP_EOL;
        }

        if (strpos($options[0], '.') !== FALSE) {
            file_put_contents($options[0], $output);
        }
        else {
            echo $output;
        }
    }

    /**
     * Generates htaccess 301 redirects for the current magento site.  Output is written
     * to stdout.
     * 
     * Usage: wiz 301-htgen <csv_file>
     * 
     * CSV File will be a two-column CSV file in the following format:
     * /old/path/to/product1.html,SKU1
     * /old/path/to/product2.html,SKU2
     *
     * @param URL to SKU CSV File.
     * @return void
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function htgenAction($options) {
        $filename = realpath($options[0]);
        if (!file_exists($filename)) {
            throw new Exception('Need a file to generate 301 mappings.');
        }
        else {
            file_put_contents('php://stderr', 'Reading current mappings from '.$filename.PHP_EOL);
        }

        Wiz::getMagento('store');

        $file_contents = file_get_contents($filename);
        $lines = explode(PHP_EOL, $file_contents);
        $redirectFormat = 'redirect 301 %s %s'.PHP_EOL;
        $output = $errors = '';

        $model = Mage::getModel('catalog/product');
        $baseUrl = Mage::getBaseUrl();
        $done = 0;

        foreach ($lines as $line) {
            $done++;
            list($url, $sku) = explode(', ', $line);
            $sku = strtoupper(trim($sku));
            $productId = $model->getIdBySku($sku);
            if ($productId === FALSE) {
                $errors .= 'Product not found for SKU# '.$sku.PHP_EOL;
            }
            $product = Mage::getModel('catalog/product')->load($productId);
            $output .= sprintf($redirectFormat, $url, str_replace($baseUrl, '/', $product->getProductUrl()));
        }

        echo $output.PHP_EOL;

        file_put_contents('php://stderr', 'Mapped '.$done.' records.'.PHP_EOL);

        if ($errors != '') {
            $errors = '========================================'.PHP_EOL
                     .'== Errors                             =='.PHP_EOL
                     .'========================================'.PHP_EOL
                     .$errors.PHP_EOL;
            file_put_contents('php://stderr', $errors);
        }
    }

    /**
     * Converts a Google Sitemap XML file to a CSV File.
     *
     * @param string $options 
     * @return void
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function xmlsm2csvAction($options) {
        $settings['path-only'] = Wiz::getWiz()->getArg('path-only');

        $filename = array_shift($options);

        switch (strtolower(substr($filename, -3))) {
            case 'xml':
                $xml = simplexml_load_file($filename);
                if ($xml->getName() != 'urlset') {
                    throw new Exception('This does not look like an XML sitemap.');
                }
                $output = fopen('php://temp', 'rw');
                foreach ($xml->url as $node) {
                    if ($settings['path-only']) {
                        $pathinfo = parse_url((string)$node->loc);
                        $url = $pathinfo['path'];
                    }
                    else {
                        $url = (string)$node->loc;
                    }
                    fputcsv($output, array($url));
                }
                rewind($output);
                while ($stuff = fgets($output, 1024)) {
                    echo $stuff;
                }
                break;
            default:
                throw new Exception('Invalid file format.');
        }
    }
}
