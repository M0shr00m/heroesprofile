<!doctype html>
<html lang="en">
    <head>
      @include('scripts.header')
    </head>
    <body >
      <div id="app">
        @include('nav.primary')
        <div class="container-fluid" >
            <div class="intro">
              <h2>{{$title}}</h2>
              <p>{{$paragraph}}</p>
            </div>
          </div>
          <div class="container-fluid bg-dark">
            @yield('content')
            @yield('datatable')
          </div>
        </div>
      </div>
      @include('scripts.footer')

    </body>
</html>