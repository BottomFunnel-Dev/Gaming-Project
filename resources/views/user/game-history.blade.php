@extends('layouts.front.front')
@section('content')

<div class="main-area" style="padding-top: 60px;">

         <nav aria-label="pagination navigation" class="MuiPagination-root d-flex justify-content-center">
            <ul class="MuiPagination">
            @if ($transactions->onFirstPage())
                <li><a href="#" aria-disabled="true"><i class="fa fa-chevron-left" aria-hidden="true"></i></a></li>
            @else
                <li><a href="{{ $transactions->previousPageUrl() }}"><i class="fa fa-chevron-left" aria-hidden="true"></i></a></li>
            @endif
               <li><a @if($page == 1 || $page == 0) class="active" @endif href="/game-history?page=1">1</a></li>
               <li><a @if($page == 2) class="active" @endif href="/game-history?page=2">2</a></li>
               <li><a @if($page == 3) class="active" @endif href="/game-history?page=3">3</a></li>
               <li><a @if($page == 4) class="active" @endif href="/game-history?page=4">4</a></li>
               <li><a @if($page == 5) class="active" @endif href="/game-history?page=5">5</a></li>
               @if ($transactions->hasMorePages())
                    <li><a href="{{ $transactions->nextPageUrl() }}" ><i class="fa fa-chevron-right" aria-hidden="true"></i></a></li>
                @else
                    <li><a href="#" aria-disabled="true"><i class="fa fa-chevron-right" aria-hidden="true"></i></a></li>
                @endif
            </ul>
         </nav>
         <script>
function myFunction() {
  location.replace("{{url('transactions')}}")
}
function myFunction2() {
  location.replace("{{url('game-history')}}")
}
function myFunction3() {
  location.replace("{{url('referral-history')}}")
}
</script>
    <div class="d-flex align-items-center justify-content-start overflow-auto pt-3 px-0 container">
      <span class="text-capitalize me-2 py-2 px-4 border text-dark badge rounded-pill" style="cursor: pointer;"onclick="myFunction()">Payment</span>
      <span class="text-capitalize me-2 py-2 px-4 border text-dark badge rounded-pill text-white bg-primary" style="cursor: pointer;"onclick="myFunction2()">Game</span>
      <span class="text-capitalize me-2 py-2 px-4 border text-dark badge rounded-pill" style="cursor: pointer;"onclick="myFunction3()">Referral</span>
    </div>
         @foreach($transactions as $id => $val)
                        @switch($val->status)
                          @case('Create')
                            {{-- @if($val->challenge->status == 0 && (isset($val->challengeresult->is_cancel) && $val->challengeresult->is_cancel == 0) && ($val->challengeresult->user_id != Auth::user()->id )) --}}
                            @if(isset($val->challenge) && $val->challenge->status == 0 && isset($val->challengeresult->is_cancel) && $val->challengeresult->is_cancel == 0 && $val->challengeresult->user_id != Auth::user()->id)

                            <div class="w-100 py-3 d-flex align-items-center list-item">
                              <div class="center-xy list-date mx-2">
                                <div>{{ date("d M", strtotime($val->created_at))}}</div><small>{{ date("H:i A", strtotime($val->created_at))}}</small>
                              </div>
                              <div class="list-divider-y"></div>
                              <div class="mx-3 d-flex list-body">
                                <div class="d-flex align-items-center"></div>
                                <div class="d-flex flex-column font-8">Lost against {{$val->challenge->oname}}   <div class="games-section-headline">Order ID:
                                      {{$val->source_id}}</div>
                                </div>
                              </div>
                              <div class="right-0 d-flex align-items-end pr-3 flex-column">
                                <div class="d-flex float-right font-8">
                                    <div class="text-danger">(-)</div>
                                    <div class="ml-1 mb-1"><img height="21px" width="21px" src="{{ asset('front/images/global-rupeeIcon.png')}}" alt="">
                                    </div><span class="pl-1">{{$val->amount}}</span>
                                </div>
                                <div class="games-section-headline" style="font-size: 0.6em;">Closing Balance: {{$val->closing_balance}}</div>
                              </div>
                            </div>

                            @endif
                            @break
                          @case('Play')
                            @if($val->challenge->status == 0 && (isset($val->challengeresult->is_cancel) && $val->challengeresult->is_cancel == 0) && ($val->challengeresult->user_id != Auth::user()->id ))
                            <div class="w-100 py-3 d-flex align-items-center list-item">
                              <div class="center-xy list-date mx-2">
                                <div>{{ date("d M", strtotime($val->created_at))}}</div><small>{{ date("H:i A", strtotime($val->created_at))}}</small>
                              </div>
                              <div class="list-divider-y"></div>
                              <div class="mx-3 d-flex list-body">
                                <div class="d-flex align-items-center"></div>
                                <div class="d-flex flex-column font-8">Lost against {{$val->challenge->cname}}     <div class="games-section-headline">Order ID:
                                      {{$val->source_id}}</div>
                                </div>
                              </div>
                              <div class="right-0 d-flex align-items-end pr-3 flex-column">
                                <div class="d-flex float-right font-8">
                                    <div class="text-danger">(-)</div>
                                    <div class="ml-1 mb-1"><img height="21px" width="21px" src="{{ asset('front/images/global-rupeeIcon.png')}}" alt="">
                                    </div><span class="pl-1">{{$val->amount}}</span>
                                </div>
                               <div class="games-section-headline" style="font-size: 0.6em;">Closing Balance: {{$val->closing_balance}}</div>
                              </div>
                            </div>
                            @endif

                            @break
                          @case('Won')
                          <div class="w-100 py-3 d-flex align-items-center list-item">
                              <div class="center-xy list-date mx-2">
                                <div>{{ date("d M", strtotime($val->created_at))}}</div><small>{{ date("H:i A", strtotime($val->created_at))}}</small>
                              </div>
                              <div class="list-divider-y"></div>
                              <div class="mx-3 d-flex list-body">
                                <div class="d-flex align-items-center"></div>
                                <div class="d-flex flex-column font-8">
                                  Won the game against
                                  @if(isset($val->challenge) && $val->challenge->c_id == Auth::user()->id)
                                    {{$val->challenge->oname}}
                                  @elseif(isset($val->challenge) && $val->challenge->o_id == Auth::user()->id)
                                    {{$val->challenge->cname}}
                                  @endif
                                  <div class="games-section-headline">Order ID:
                                      {{$val->source_id}}</div>
                                </div>
                              </div>
                              <div class="right-0 d-flex align-items-end pr-3 flex-column">
                                <div class="d-flex float-right font-8">
                                    <div class="text-success">(+)</div>
								    <div class="ml-1 mb-1"><img height="21px" width="21px" src="{{ asset('front/images/global-rupeeIcon.png')}}" alt="">
                                    </div><span class="pl-1">{{$val->amount }}</span>
                                </div>
                             <div class="games-section-headline" style="font-size: 0.6em;">Closing Balance: {{$val->closing_balance}}</div>
                              </div>
                            </div>
                            @break
                          @case('Cancel')
                          <div class="w-100 py-3 d-flex align-items-center list-item">
                              <div class="center-xy list-date mx-2">
                                <div>{{ date("d M", strtotime($val->created_at))}}</div><small>{{ date("H:i A", strtotime($val->created_at))}}</small>
                              </div>
                              <div class="list-divider-y"></div>
                              <div class="mx-3 d-flex list-body">
                                <div class="d-flex align-items-center"></div>
                                <div class="d-flex flex-column font-8">
                                  Cancel game against
                                  @if(isset($val->challenge) && $val->challenge->c_id == Auth::user()->id)
                                    {{$val->challenge->oname}}
                                  @elseif(isset($val->challenge) && $val->challenge->o_id == Auth::user()->id)
                                    {{$val->challenge->cname}}
                                  @endif
                                  <div class="games-section-headline">Order ID:
                                      {{$val->source_id}}</div>
                                </div>
                              </div>
                              <div class="right-0 d-flex align-items-end pr-3 flex-column">
                                <div class="d-flex float-right font-8">
                                    <!-- <div class="text-success">(+)</div> -->
                                    <div class="ml-1 mb-1"><img height="21px" width="21px" src="{{ asset('front/images/global-rupeeIcon.png')}}" alt="">
                                    </div><span class="pl-1">{{$val->amount}}</span>
                                </div>
                              <div class="games-section-headline" style="font-size: 0.6em;">Closing Balance: {{$val->closing_balance}}</div>
                              </div>
                            </div>

                            @break
                          @endswitch
                @endforeach

        @if($transactions->count() == 0 )
         <div class="cxy flex-column px-4 text-center" style="margin-top: 70px;"><img src="{{asset('front/images/no-data.jpg')}}"
               width="280px" alt="">
            <div class="games-section-title mt-4" style="font-size: 1.2em;">No transactions yet!</div>
            <div class="games-section-headline mt-2" style="font-size: 0.85em;">Seems like you haven’t done any activity
               yet</div>
         </div>
         @endif
      </div>
      <div class="divider-y"></div>

@endsection

