<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $articleUrl }}">
    @if($image)
    <meta property="og:image" content="{{ $image }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    @if($image)
    <meta name="twitter:image" content="{{ $image }}">
    @endif
    <meta http-equiv="refresh" content="0;url={{ $articleUrl }}">
</head>
<body data-article-url="{{ $articleUrl }}">
    <script>
        const articleUrl = document.body?.dataset?.articleUrl;
        if (articleUrl) {
            window.location.replace(articleUrl);
        }
    </script>
</body>
</html>
