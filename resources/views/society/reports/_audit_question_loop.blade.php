{{-- One label + textarea per question. $questions, $fieldPrefix (the JSON column name), $savedAnswers (code => text) --}}
@foreach($questions as $q)
    <div class="form-group" style="margin-bottom:12px">
        <label style="display:block;font-weight:600">
            @if(!empty($q['section']))({{ $q['section'] }}) @endif{{ $q['en'] }}<br><small>{{ $q['mr'] }}</small>
        </label>
        <textarea class="form-control" rows="2" style="width:100%;padding:6px;border:1px solid #ccd;border-radius:4px"
                  name="{{ $fieldPrefix }}[{{ $q['code'] }}]"
                  placeholder="{{ trim($q['default_mr'] . ' / ' . $q['default_en'], ' /') }}">{{ !empty($savedAnswers[$q['code']]) ? $savedAnswers[$q['code']] : '' }}</textarea>
    </div>
@endforeach
