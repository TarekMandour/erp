{{-- Aside --}}
<div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title">
                <h2>الإجراءات</h2>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="reset" class="btn btn-light me-3">الغاء</button>
            <button type="submit" class="btn btn-primary">حفظ</button>
        </div>
    </div>
</div>

{{-- Main --}}
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title">
                <h2>بيانات مركز التكلفة</h2>
            </div>
        </div>
        <div class="card-body pt-0">

            <div class="row mb-7">
                <div class="col-md-6 fv-row">
                    <label class="form-label required">الكود</label>
                    <input type="text" class="form-control form-control-solid" name="code"
                        value="{{old('code', $data->code ?? '')}}" placeholder="CC-001" />
                    @error('code')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-6 fv-row">
                    <label class="form-label required">الاسم</label>
                    <input type="text" class="form-control form-control-solid" name="name"
                        value="{{old('name', $data->name ?? '')}}" placeholder="اسم مركز التكلفة" />
                    @error('name')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

            <div class="row mb-7">
                <div class="col-md-6 fv-row">
                    <label class="form-label">المركز الأب</label>
                    <select class="form-select form-select-solid" name="parent_id">
                        <option value="">-- بدون أب --</option>
                        @foreach($parents as $parent)
                            <option value="{{$parent->id}}" @if(old('parent_id', $data->parent_id ?? '') == $parent->id) selected @endif>
                                {{$parent->code}} - {{$parent->name}}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-6 fv-row d-flex align-items-end pb-2">
                    <div class="form-check form-switch form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                            @if(old('is_active', $data->is_active ?? true)) checked @endif />
                        <label class="form-check-label fw-semibold" for="is_active">نشط</label>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
