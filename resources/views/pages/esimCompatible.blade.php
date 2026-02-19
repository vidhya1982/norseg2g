<div class="esim-page">
  <div class="container py-5">

    <!-- HERO -->
    <div class="esim-hero mb-5">
      <h1>
        {{ __('staticpages/esim_compatible.hero.title') }}
        <span>{{ __('staticpages/esim_compatible.hero.highlight') }}</span>
      </h1>

      <p>{{ __('staticpages/esim_compatible.hero.subtitle') }}</p>
      <small>{{ __('staticpages/esim_compatible.hero.updated') }}</small>
    </div>

    <!-- APPLE -->
    <div class="esim-card">
      <div class="brand-title">{{ __('staticpages/esim_compatible.brands.apple') }}</div>

      <ul class="device-list">
        @foreach(__('staticpages/esim_compatible.devices.apple') as $device)
          <li>{{ $device }}</li>
        @endforeach
      </ul>

      <div class="note-box">
        {{ __('staticpages/esim_compatible.notes.apple') }}
      </div>
    </div>

    <!-- GOOGLE PIXEL -->
    <div class="esim-card">
      <div class="brand-title">{{ __('staticpages/esim_compatible.brands.google') }}</div>

      <ul class="device-list">
        @foreach(__('staticpages/esim_compatible.devices.google') as $device)
          <li>{{ $device }}</li>
        @endforeach
      </ul>
    </div>

    <!-- SAMSUNG -->
    <div class="esim-card">
      <div class="brand-title">{{ __('staticpages/esim_compatible.brands.samsung') }}</div>

      <ul class="device-list">
        @foreach(__('staticpages/esim_compatible.devices.samsung') as $device)
          <li>{{ $device }}</li>
        @endforeach
      </ul>

      <div class="warning-box">
        <strong>{{ __('staticpages/esim_compatible.warning.title') }}</strong><br>

        <ul>
          @foreach(__('staticpages/esim_compatible.warning.items') as $item)
            <li>{{ $item }}</li>
          @endforeach
        </ul>
      </div>
    </div>

    <!-- HUAWEI -->
    <div class="esim-card">
      <div class="brand-title">{{ __('staticpages/esim_compatible.brands.huawei') }}</div>

      <ul class="device-list">
        @foreach(__('staticpages/esim_compatible.devices.huawei') as $device)
          <li>{{ $device }}</li>
        @endforeach
      </ul>

      <div class="note-box">
        {{ __('staticpages/esim_compatible.notes.huawei') }}
      </div>
    </div>

    <!-- OPPO -->
    <div class="esim-card">
      <div class="brand-title">{{ __('staticpages/esim_compatible.brands.oppo') }}</div>

      <ul class="device-list">
        @foreach(__('staticpages/esim_compatible.devices.oppo') as $device)
          <li>{{ $device }}</li>
        @endforeach
      </ul>

      <div class="note-box">
        {{ __('staticpages/esim_compatible.notes.oppo') }}
      </div>
    </div>

    <!-- SONY -->
    <div class="esim-card">
      <div class="brand-title">{{ __('staticpages/esim_compatible.brands.sony') }}</div>

      <ul class="device-list">
        @foreach(__('staticpages/esim_compatible.devices.sony') as $device)
          <li>{{ $device }}</li>
        @endforeach
      </ul>
    </div>

    <!-- XIAOMI -->
    <div class="esim-card">
      <div class="brand-title">{{ __('staticpages/esim_compatible.brands.xiaomi') }}</div>

      <ul class="device-list">
        @foreach(__('staticpages/esim_compatible.devices.xiaomi') as $device)
          <li>{{ $device }}</li>
        @endforeach
      </ul>
    </div>

    <!-- MOTOROLA -->
    <div class="esim-card">
      <div class="brand-title">{{ __('staticpages/esim_compatible.brands.motorola') }}</div>

      <ul class="device-list">
        @foreach(__('staticpages/esim_compatible.devices.motorola') as $device)
          <li>{{ $device }}</li>
        @endforeach
      </ul>
    </div>

    <!-- OTHERS -->
    <div class="esim-card">
      <div class="brand-title">{{ __('staticpages/esim_compatible.brands.others') }}</div>

      <ul class="device-list">
        @foreach(__('staticpages/esim_compatible.devices.others') as $device)
          <li>{{ $device }}</li>
        @endforeach
      </ul>
    </div>

    <!-- UNLOCK NOTE -->
    <div class="note-box">
      <strong>{{ __('staticpages/esim_compatible.notes.unlock') }}</strong><br>
      {{ __('staticpages/esim_compatible.notes.unlock_text') }}
    </div>

  </div>
</div>
