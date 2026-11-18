{{-- الحساب المقابل لقيد حركة المخزون اليدوية (QA الدفعة 4) --}}
@php $defaultCounter = optional($counterAccounts->firstWhere('code', '5012'))->id; @endphp
<label class="form-label required">الحساب المقابل</label>
<select class="form-select form-select-solid" name="counter_account_id" data-control="select2" data-placeholder="اختر الحساب المقابل">
    @foreach($counterAccounts as $acc)
        <option value="{{$acc->id}}" {{ old('counter_account_id', $defaultCounter) == $acc->id ? 'selected' : '' }}>{{$acc->code}} - {{$acc->name}}</option>
    @endforeach
</select>
<div class="text-muted fs-7 mt-1">يُنشأ قيد مرحّل: الوارد مدين 1103 المخزون ودائن هذا الحساب، والصادر بالعكس. للرصيد الافتتاحي اختر 3001 رأس المال.</div>
@error('counter_account_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
