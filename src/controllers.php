<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function Stringy\create as s;

function generateWord($length)
{

    $words = preg_split('/\\n/', file_get_contents(__DIR__ . '/../fi_50k.txt'));

    do {

        $randomWord = s($words[random_int(0, count($words) - 1)])->toAscii()->split(' ')[0];

    } while ($randomWord->length() != $length);
    
    return $randomWord;

}

function random_str($length, $keyspace = 'abcdefghijklmnopqrstuvwxyz')
{
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
}

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig', array());
})->bind('homepage');

$app->get('/info', function () use ($app) {
    return $app['twig']->render('info.html.twig', array());
})->bind('info');

$app->post('/info-submit', function (Request $request) use ($app) {

    var_dump($request->request->all());

    return $app->redirect('/question');

})->bind('info-submit');


$app->match('/question', function (Request $request) use ($app) {

    $session = $request->getSession();

    if (!empty($request->request->all()['word'])) {

        $session->set(
            'count', $session->get('count', 0) + 1
        );

        if ($request->request->all()['recall'] === $request->request->all()['word']) {

            $session->set(
                'recall', $session->get('recall', 0) + 1
            );

        }

    }

    $length = $request->get('length', 10);
    $random = $request->get('random', false);

    return $app['twig']->render('question.html.twig', [
        'word' => $random ? random_str($length) : generateWord($length),
        'count' => $session->get('count', 0),
        'recall' => $session->get('recall', 0)
    ]);

})->bind('question');

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/' . $code . '.html.twig',
        'errors/' . substr($code, 0, 2) . 'x.html.twig',
        'errors/' . substr($code, 0, 1) . 'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
