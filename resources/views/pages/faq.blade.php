<div class="container py-5 faq">

    <!-- ================= HERO SECTION ================= -->
    <div class="faq-hero mb-5 p-5 rounded">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fw-bold">
                    {{ __('staticpages/faq.hero.title') }}
                </h1>
                <p class="text-muted mt-3">
                    {{ __('staticpages/faq.hero.description') }}
                </p>
            </div>
        </div>
    </div>

    <!-- ================= SEARCH INPUT ================= -->
    <div class="mb-4">
        <input type="text" id="faqSearch" class="form-control form-control-lg"
            placeholder="Search FAQs (e.g. gsm2go, eSIM, plans...)"
            autocomplete="off">
    </div>

    <!-- ================= FAQ ACCORDION ================= -->
    <div class="accordion" id="faqAccordion">

        @foreach ($faqs as $index => $faq)
            @php
                $question = $faq->{'q_' . $lang};
                $answer   = $faq->{'ans_' . $lang};
            @endphp

            <div class="accordion-item faq-item mb-3">
                <h2 class="accordion-header">
                    <button
                        class="accordion-button {{ $index !== 0 ? 'collapsed' : '' }}"
                        data-bs-toggle="collapse"
                        data-bs-target="#faq{{ $faq->id }}">
                        {!! $question !!}
                    </button>
                </h2>

                <div id="faq{{ $faq->id }}"
                    class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
                    data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        {!! $answer !!}
                    </div>
                </div>
            </div>
        @endforeach

    </div>

    <!-- ================= NO RESULT MESSAGE ================= -->
    <div id="noResult" class="text-center text-muted mt-4" style="display:none;">
        No FAQs found for your search.
    </div>

</div>
