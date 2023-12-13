<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta property="og:article:section" content="{{$gen->section}}" />

        @if(Route::current()->getName()=='article')
            @yield('article-head')
        @else
            <meta name="description" content="{{$gen->description}}">
            <meta property="og:title" content="{{$gen->title}}" />
            <meta property="og:type" content="article" />
            <meta property="og:url" content="{{url('/')}}" />
            <meta property="og:image" content="{{$gen->brand_image=='default' || !Storage::disk('public')->exists($gen->brand_image) ? asset('images/sbblog.png') : asset('storage/'. $gen->brand_image)}}" />
            <meta name="twitter:card" content="summary_large_image">
            <meta property="twitter:domain" content="{{request()->getHttpHost();}}">
            <meta property="twitter:url" content="{{url('/')}}">
            <meta name="twitter:title" content="{{$gen->title}}">
            <meta name="twitter:image" content="{{$gen->brand_image=='default' || !Storage::disk('public')->exists($gen->brand_image) ? asset('images/sbblog.png') : asset('storage/'. $gen->brand_image)}}">
            <title>{{$gen->title. ' - '. $gen->slogan}}</title>
        @endif

        <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet">
        <link href="{{asset('css/main.css')}}" rel="stylesheet">
        @isset($gen->google_ads_script)
        {!!$gen->google_ads_script!!}
        @endisset
        @isset($gen->google_optimize_script)
        {!!$gen->google_optimize_script!!}
        @endisset

        @yield('recaptcha')
    </head>
    <body>
        <div class="container-md">
            <div class="row">
                <div class="col-md-12" style="text-align:right;">
                    <ul class="list-inline">
                        @foreach ($gen->socialNetworkUrls()->get() as $url)
                            <li class="list-inline-item">
                                <a href="{{$url->url}}" target="_blank">
                                    <img src="{{asset($url->socialNetwork()->get()->first()->icon)}}" alt="{{$url->socialNetwork->get()->first()->name}}" title="{{$url->socialNetwork->get()->first()->name}}">
                                </a>
                            </li>
                        @endforeach
                        <li class="list-inline-item"></li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12" style="text-align:center;">
                    <a href="{{route('latestPage')}}">
                        <img src="{{$gen->brand_image=='default' || !Storage::disk('public')->exists($gen->getBrandImage()) ? asset('images/sbblog.png') : asset('storage/'. $gen->getBrandImage())}}" alt="{{$gen->title}}">
                    </a>
                    <p class="slogan">{{$gen->slogan}}</p>
                </div>
                <hr>
            </div>

            <div class="row mt25">
                <div class="col-md-9">
                    @yield('page')
                </div>

                <div class="col-md-3" style="text-align:center;">
                    <div class="card">
                        <h5 class="card-header">Categorias</h5>
                        <div class="card-body" style="text-align:left;">
                            <ul class="nav flex-column">
                                @forelse($category as $cat)
                                <li class="nav-item">
                                    <a class="nav-link" href="{{route('latestPageCategory', ['id' => $cat->id, 'category' => $cat->category])}}">{{$cat->category}}</a>
                                </li>
                                @empty
                                <li class="nav-item">
                                    <a class="nav-link" href="#">{{__('sbblog.no.category')}}</a>
                                </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                    
                    <a href="https://sysborg.com.br" target="_blank" title="Sysborg">
                        <img src="{{asset('images/publicidade1.png')}}" alt="Espaço para publicidade">
                    </a>
                </div>
            </div>

            <div class="row mt25">
                <div class="col-md-4 offset-md-3" style="padding-bottom: 20px;">
                    Todos os direitos reservados. © 2021-2031
                </div>
                <div class="col-md-5" style="text-align: right;">
                    SBBlog Powered By <a href="https://sysborg.com.br" target="_blank" title="https://sysborg.com.br">
                        <img src="{{asset('images/poweredby.png')}}" alt="Powered By Sysborg">
                    </a> <span style="margin-left:10px; margin-right:10px;">|</span> <a href="https://andersonarruda.com.br" target="_blank" title="https://andersonarruda.com.br">
                        <img src="{{asset('images/poweredby2.png')}}" alt="Powered By Anderson Arruda">
                    </a>
                </div>
            </div>
        </div>

        <script src="{{asset('js/bootstrap.bundle.min.js')}}"></script>
        <script src="{{asset('js/fontawesome/all.min.js')}}"></script>
        @isset($gen->google_analytics)
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{$gen->google_analytics}}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', '{{$gen->google_analytics}}');
        </script>
        @endisset
    </body>
</html>