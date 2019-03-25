<?php
foreach (glob(__DIR__.'/*_controller.php') as $controller) {
    $cname = basename($controller, '.php');
    if (!empty($cname)) {
        require_once $controller;
    }
}

foreach (glob(__DIR__.'/../components/*.php') as $component) {
    $cname = basename($component, '.php');
    if (!empty($cname)) {
        require_once $component;
    }
}

$app->get('/product[/{category}[/{product}]]', function ($request, $response, $args) {
    $pmodel = new \ExtensionsModel\ProductModel();
    $model = \ExtensionsModel\ProductModel::model()->findByAttributes(['slug' => $args['product']]);
    if ($model instanceof \RedBeanPHP\OODBBean) {

        return $this->view->render($response, 'product_detail.phtml', [
            'model' => $model
        ]);
    }
});

$app->group('/products', function () use ($user) {
    $this->group('/category', function() use ($user) {
        new Extensions\Controllers\ProductCategoryController($this, $user);
    });
    $this->group('/default', function() use ($user) {
        new Extensions\Controllers\ProductDefaultController($this, $user);
    });
});

?>
