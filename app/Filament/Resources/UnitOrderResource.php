<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitOrderResource\Pages;
use App\Models\UnitOrder;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Unit;
use App\Filament\Resources\UnitOrderResource\Widgets\UnitOrderStats;
use Filament\Notifications\Notification;

use App\Exports\UnitOrdersExport;
use Filament\Tables\Actions\ExportAction;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;

class UnitOrderResource extends Resource
{
    protected static ?string $model = UnitOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Projects';

    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'طلبات الوحدات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        // user inputs
                        Grid::make()
                            ->columns(3)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Toggle::make('status')
                            ->default(false),
                        Forms\Components\Textarea::make('message')
                            ->nullable(),

                        Grid::make()
                            ->columns(2)
                            ->schema([
                                Forms\Components\Select::make('PurchaseType')
                                    ->options([
                                        'Cash' => 'كاش',
                                        'Installment' => 'بنك',
                                    ])
                                    ->required(),
                                Forms\Components\Select::make('PurchasePurpose')
                                    ->options([
                                        'Residential' => 'سكني',
                                        'Commercial' => 'استثماري',
                                    ])
                                    ->required(),
                            ]),

                        Grid::make()
                            ->columns(3)
                            ->schema([
                                Forms\Components\BelongsToSelect::make('unit_id')
                                    ->relationship('unit', 'title')
                                    ->required(),
                                Forms\Components\BelongsToSelect::make('user_id')
                                    ->relationship('user', 'name')
                                    ->required(),
                                Forms\Components\BelongsToSelect::make('project_id')
                                    ->relationship('project', 'name')
                                    ->required(),
                            ])
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_info')
                    ->label('المعلومات')
                    ->getStateUsing(function ($record) {
                        return "<strong>{$record->name}</strong><br>{$record->email}<br>{$record->phone}";
                    })
                    ->html() // Enable HTML rendering for the column
                    ->sortable(['name', 'email', 'phone']) // Make it sortable
                    ->searchable(['name', 'email', 'phone']),

                Tables\Columns\SelectColumn::make('status')
                    ->label('الحالة')
                    ->options([
                        0 => 'جديد',
                        1 => 'قيد المعالجة',
                        2 => 'مكتمل',
                        3 => 'ملغي',
                    ])
                    ->default(fn ($record) => $record->status) // Set the default value based on the current status
                    ->searchable()
                    ->afterStateUpdated(function ($state) {
                        // Show a success notification after state update
                        Notification::make()
                            ->title('Status Updated')
                            ->body('تم تحديث الحالة بنجاح')
                            ->success()
                            ->send();
                    }),
                Tables\Columns\TextColumn::make('PurchaseType')->label('طريقة الشراء')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('PurchasePurpose')->label('الغرض من الشراء')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('project.name')->label('المشروع')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('unit.title')->label('الوحدة')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                // advanced filters
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Active' => 'Active',
                        'Inactive' => 'Inactive',
                    ]),
                Tables\Filters\SelectFilter::make('PurchaseType')
                    ->options([
                        'Cash' => 'كاش',
                        'Installment' => 'بنك',
                    ]),
                Tables\Filters\SelectFilter::make('PurchasePurpose')
                    ->options([
                        'Residential' => 'سكني',
                        'Commercial' => 'استثماري',
                    ]),

                Tables\Filters\SelectFilter::make('unit_id')
                    ->label('Unit')
                    ->relationship('unit', 'title')
                    ->options(fn (): Builder => Unit::query()->orderBy('title')->pluck('title', 'id')->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            // ->headerActions([
            //     ExportAction::make('export')
            //         ->label('تصدير إلى Excel')
            //         ->icon('heroicon-o-document')
            //         ->action(function () {
            //             return Excel::download(new UnitOrdersExport, 'unit-orders.xlsx');
            //         }),
            // ])
            ->bulkActions([
                BulkAction::make('exportSelected')
                    ->label('تصدير المحدد إلى Excel')
                    ->icon('heroicon-o-document')
                    ->action(function (Collection $records) {
                        return Excel::download(new UnitOrdersExport($records), 'selected-unit-orders.xlsx');
                    }),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnitOrders::route('/'),
            'create' => Pages\CreateUnitOrder::route('/create'),
            'edit' => Pages\EditUnitOrder::route('/{record}/edit'),
        ];
    }

    public static function widgets(): array
    {
        return [
            UnitOrderStats::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (auth()->user()->hasRole('Admin')) {
            return $query;
        }
        // Check if the user has the 'sales_manager' role
        if (auth()->user()->hasRole('sales_manager')) {
            // Filter unit orders based on the project's sales_manager_id through the unit relationship
            $query->whereHas('unit.project', function ($query) {
                $query->where('sales_manager_id', auth()->user()->id);
            });
        }

        return $query;
    }

}
