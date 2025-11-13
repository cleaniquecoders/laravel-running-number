<?php

namespace CleaniqueCoders\RunningNumber\Http\Controllers;

use CleaniqueCoders\RunningNumber\Contracts\Generator;
use CleaniqueCoders\RunningNumber\Exceptions\ConfigurationException;
use CleaniqueCoders\RunningNumber\Exceptions\InvalidRunningNumberTypeException;
use CleaniqueCoders\RunningNumber\Exceptions\MaxNumberReachedException;
use CleaniqueCoders\RunningNumber\Models\RunningNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

/**
 * REST API Controller for Running Numbers
 *
 * Provides HTTP endpoints for generating and checking running numbers.
 *
 * @example
 * ```php
 * // In routes/api.php
 * Route::prefix('running-numbers')->group(function () {
 *     Route::post('generate', [RunningNumberController::class, 'generate']);
 *     Route::get('current', [RunningNumberController::class, 'current']);
 *     Route::get('preview', [RunningNumberController::class, 'preview']);
 *     Route::get('list', [RunningNumberController::class, 'list']);
 * });
 * ```
 */
class RunningNumberController extends Controller
{
    public function __construct(
        private Generator $generator
    ) {}

    /**
     * Generate a new running number
     *
     * POST /api/running-numbers/generate
     *
     *
     * @throws InvalidRunningNumberTypeException
     * @throws MaxNumberReachedException
     * @throws ConfigurationException
     */
    public function generate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string',
            'scope' => 'nullable|string',
            'start_from' => 'nullable|integer',
            'max_number' => 'nullable|integer|min:1',
            'presenter' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->generator->type($request->input('type'));

            if ($request->has('scope')) {
                $this->generator->scope($request->input('scope'));
            }

            if ($request->has('start_from')) {
                $this->generator->startFrom($request->integer('start_from'));
            }

            if ($request->has('max_number')) {
                $this->generator->maxNumber($request->integer('max_number'));
            }

            if ($request->has('presenter')) {
                $presenterClass = $request->input('presenter');
                if (class_exists($presenterClass)) {
                    $this->generator->formatter(new $presenterClass);
                }
            }

            $number = $this->generator->generate();

            /** @var RunningNumber|null $model */
            $model = RunningNumber::where('type', strtoupper($request->input('type')))
                ->when($request->has('scope'), function ($query) use ($request) {
                    return $query->where('scope', $request->input('scope'));
                }, function ($query) {
                    return $query->whereNull('scope');
                })
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'number' => $number,
                    'type' => $model?->type,
                    'scope' => $model?->scope,
                    'current_count' => $model?->number,
                    'uuid' => $model?->uuid,
                    'reset_period' => is_object($model?->reset_period) ? $model->reset_period->value : $model?->reset_period,
                    'last_reset_at' => $model?->last_reset_at?->toIso8601String(),
                    'created_at' => $model?->created_at?->toIso8601String(),
                ],
            ], 201);
        } catch (InvalidRunningNumberTypeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid running number type',
                'error' => $e->getMessage(),
            ], 400);
        } catch (MaxNumberReachedException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum number reached',
                'error' => $e->getMessage(),
            ], 422);
        } catch (ConfigurationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration error',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current running number information
     *
     * GET /api/running-numbers/current?type=invoice&scope=retail
     */
    public function current(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string',
            'scope' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        /** @var RunningNumber|null $model */
        $model = RunningNumber::where('type', strtoupper($request->input('type')))
            ->when($request->has('scope'), function ($query) use ($request) {
                return $query->where('scope', $request->input('scope'));
            }, function ($query) {
                return $query->whereNull('scope');
            })
            ->first();

        if (! $model) {
            return response()->json([
                'success' => false,
                'message' => 'Running number not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'uuid' => $model->uuid,
                'type' => $model->type,
                'scope' => $model->scope,
                'current_number' => $model->number,
                'reset_period' => is_object($model->reset_period) ? $model->reset_period->value : $model->reset_period,
                'last_reset_at' => $model->last_reset_at?->toIso8601String(),
                'created_at' => $model->created_at->toIso8601String(),
                'updated_at' => $model->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Preview next running number without generating
     *
     * GET /api/running-numbers/preview?type=invoice&scope=retail
     */
    public function preview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string',
            'scope' => 'nullable|string',
            'start_from' => 'nullable|integer',
            'presenter' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->generator->type($request->input('type'));

            if ($request->has('scope')) {
                $this->generator->scope($request->input('scope'));
            }

            if ($request->has('start_from')) {
                $this->generator->startFrom($request->integer('start_from'));
            }

            if ($request->has('presenter')) {
                $presenterClass = $request->input('presenter');
                if (class_exists($presenterClass)) {
                    $this->generator->formatter(new $presenterClass);
                }
            }

            $preview = $this->generator->preview();

            return response()->json([
                'success' => true,
                'data' => [
                    'preview' => $preview,
                    'type' => $request->input('type'),
                    'scope' => $request->input('scope'),
                ],
            ]);
        } catch (InvalidRunningNumberTypeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid running number type',
                'error' => $e->getMessage(),
            ], 400);
        } catch (ConfigurationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration error',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List all running numbers with optional filters
     *
     * GET /api/running-numbers/list?type=invoice&scope=retail
     */
    public function list(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|string',
            'scope' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = RunningNumber::query();

        if ($request->has('type')) {
            $query->where('type', strtoupper($request->input('type')));
        }

        if ($request->has('scope')) {
            $query->where('scope', $request->input('scope'));
        }

        $perPage = $request->integer('per_page', 15);
        $runningNumbers = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = $runningNumbers->map(function ($model) {
            return [
                'uuid' => $model->uuid,
                'type' => $model->type,
                'scope' => $model->scope,
                'current_number' => $model->number,
                'reset_period' => is_object($model->reset_period) ? $model->reset_period->value : $model->reset_period,
                'last_reset_at' => $model->last_reset_at?->toIso8601String(),
                'created_at' => $model->created_at->toIso8601String(),
                'updated_at' => $model->updated_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $runningNumbers->currentPage(),
                'per_page' => $runningNumbers->perPage(),
                'total' => $runningNumbers->total(),
                'last_page' => $runningNumbers->lastPage(),
            ],
        ]);
    }
}
