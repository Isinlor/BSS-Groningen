<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function Stringy\create as s;

function generateWord(array $seenWords)
{

    $words = [
        'kuitenkaan',
        'muistuttaa',
        'osallistua',
        'työntekijä',
        'kirjoittaa',
        'keskustelu',
        'neuvottelu',
        'tarkoittaa',
        'puolestaan',
        'tavallinen',
        'merkittävä',
        'huolimatta',
        'vaihtoehto',
        'valmentaja',
        'opiskelija',
        'vaihteeksi',
        'terminaali',
        'ihastuttaa',
        'tilastoida',
        'työllistyä',
        'myyntitulo',
        'kulloinkin',
        'parannella',
        'pysäköinti',
        'kaksisataa',
        'paperikone',
        'silmäkulma',
        'tyhjillään',
        'ryhtyminen',
        'viikottain'
    ];

    $words = array_map(
        function ($word) {
            return s($word)->toAscii();
        },
        $words
    );

    do {

        $randomWord = $words[random_int(0, count($words) - 1)];

    } while (in_array($randomWord, $seenWords));

    return $randomWord;

}

$app->before(function (Request $request) use ($app) {

    if ($request->get('redirect', false)) {
        return;
    }

    $session = $request->getSession();

    $user = $session->get('user', false);
    $tutorial = $session->get('tutorial', false);
    $finished = $session->get('finished', false);

    if ($finished) {
        return $app->redirect($app['url_generator']->generate('end', ['redirect' => true]));
    }

    if ($request->getPathInfo() !== '/tutorial' && $user && !$tutorial) {
        return $app->redirect($app['url_generator']->generate('tutorial', ['redirect' => true]));
    }

    if ($request->getPathInfo() !== '/question' && $user && $tutorial && !$finished) {
        return $app->redirect($app['url_generator']->generate('question', ['redirect' => true]));
    }

});

$app->get('/', function () use ($app) {

    return $app['twig']->render('index.html.twig', array());

})->bind('homepage');

$app->get('/info', function () use ($app) {

    return $app['twig']->render('info.html.twig', array());

})->bind('info');

$app->post('/info-submit', function (Request $request) use ($app) {

    $data = $request->request->all();

    /** @var \Doctrine\DBAL\Connection $conn */
    $conn = $app['db'];

    $conn->insert('users', [
        'email' => $data['email'],
        'birth' => $data['birth'],
        'first_language' => $data['first-language'],
        'other_languages' => $data['secound-language'],
        'gender' => $data['gender'],
        'finnish' => $data['finnish']
    ]);

    $session = $request->getSession();

    $session->set('user', $conn->lastInsertId());

    return $app->redirect($app['url_generator']->generate('question'));

})->bind('info-submit');


$app->match('/question', function (Request $request) use ($app) {

    $session = $request->getSession();

    $order = $session->get('order', 1);

    if ($request->isMethod('post')) {

        /** @var \Doctrine\DBAL\Connection $conn */
        $conn = $app['db'];

        $data = $request->request->all();

        $conn->insert('words', [
            'user' => (int)$session->get('user'),
            'given_word' => $session->get('word'),
            'recalled_word' => $data['recall'],
            'generation' => $session->get('generation'),
            'response' => time(),
            '`order`' => $order
        ]);

        if ($order >= 30) {

            $session->set('finished', true);

            return $app->redirect('/end');
        }

        $session->set('order', $order + 1);
        $session->remove('word');
        $session->remove('seen');

    }

    if (!$session->has('word')) {

        $session->set('word', $word = generateWord(
            $seenWords = $session->get('seenWords', [])
        ));

        $seenWords[] = $word;

        $session->set('seenWords', $seenWords);

        $session->set('generation', time());

    }


    $session->set('seen', $session->get('seen', 0) + 1);

    return $app['twig']->render('question.html.twig', [
        'show' => $session->get('seen') === 1,
        'word' => $session->get('word'),
        'toGo' => 30 - $order
    ]);

})->bind('question');

$app->match('/end', function (Request $request) use ($app) {

    /** @var \Doctrine\DBAL\Connection $conn */
    $conn = $app['db'];

    $session = $request->getSession();

    $score = $conn->fetchColumn(
        'SELECT COUNT(*) FROM `words` WHERE `user` = ? AND `recalled_word` = `given_word`', [
        $session->get('user')
    ]);

    return $app['twig']->render('end.html.twig', [
        'score' => $score
    ]);

})->bind('end');

$app->match('/tutorial', function (Request $request) use ($app) {

    if ($request->isMethod('post')) {

        $session = $request->getSession();

        $session->set('tutorial', true);

        return $app->redirect($app['url_generator']->generate('question'));

    }

    return $app['twig']->render('tutorial.html.twig');

})->bind('tutorial');

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
