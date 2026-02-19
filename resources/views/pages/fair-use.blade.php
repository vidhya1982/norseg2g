<div class="fairuse-page">

  <!-- HERO -->
  <div class="container py-5">
    <div class="fairuse-hero">
      <h1>
        {{ __('staticpages/fairuse.hero.title') }}
        <span>{{ __('staticpages/fairuse.hero.highlight') }}</span>
      </h1>

      <p>
        {{ __('staticpages/fairuse.hero.subtitle') }}
      </p>

      <small>
        {{ __('staticpages/fairuse.hero.updated') }}
      </small>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="container pb-5">

    <div class="fairuse-card">
      <p>{{ __('staticpages/fairuse.intro.p1') }}</p>
      <p>{{ __('staticpages/fairuse.intro.p2') }}</p>
      <p>{{ __('staticpages/fairuse.intro.p3') }}</p>
      <p>{{ __('staticpages/fairuse.intro.p4') }}</p>
    </div>

    <div class="fairuse-card">
      <h5>{{ __('staticpages/fairuse.reasonable.title') }}</h5>
      <p>{{ __('staticpages/fairuse.reasonable.p1') }}</p>

      <ul class="fairuse-list">
        @foreach((array) trans('staticpages/fairuse.reasonable.items') as $item)
          <li>{{ $item }}</li>
        @endforeach
      </ul>
    </div>

    <div class="fairuse-card">
      <h5>{{ __('staticpages/fairuse.exceeds.title') }}</h5>

      <p>{{ __('staticpages/fairuse.exceeds.p1') }}</p>

      <ul class="fairuse-list">
        @foreach((array) trans('staticpages/fairuse.exceeds.items') as $item)
          <li>{{ $item }}</li>
        @endforeach
      </ul>
    </div>

    <div class="fairuse-card">
      <p>
        {{ __('staticpages/fairuse.footer.text') }}
      </p>
    </div>

  </div>
</div>
