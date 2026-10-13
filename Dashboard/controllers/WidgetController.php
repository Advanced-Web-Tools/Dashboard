<?php
namespace Dashboard\controllers;
use Dashboard\service\{WidgetAuthentication, WidgetService, LayoutConflict};
use response\Response;
final class WidgetController extends \controller\Controller
{
    public \event\EventDispatcher $eventDispatcher;
    public function index(array|string $params = []): Response
    {
        return $this->run("state");
    }
    public function visibility(array|string $params = []): Response
    {
        return $this->run("visibility");
    }
    public function layout(array|string $params = []): Response
    {
        return $this->run("layout");
    }
    public function reset(array|string $params = []): Response
    {
        return $this->run("reset");
    }
    private function run(string $action): Response
    {
        $user = (new WidgetAuthentication())->current();
        if (!$user) {
            return Response::make(401, "Please sign in again.")
                ->data([])
                ->asJson()
                ->header("Cache-Control", "no-store");
        }
        try {
            $service = new WidgetService($this->eventDispatcher, $user);
            if ($action === "state") {
                return $this->respond($service->state());
            }
            $allowed = match ($action) {
                "visibility" => ["key", "visible", "revision", "_token"],
                "layout" => ["layout", "revision", "_token"],
                default => ["revision", "_token"],
            };
            if (array_diff(array_keys($_POST), $allowed)) {
                throw new \InvalidArgumentException("Unexpected request fields.");
            }
            $revision = $_POST["revision"] ?? null;
            if (!is_string($revision) || !preg_match('/^(0|[1-9][0-9]{0,8})$/D', $revision)) {
                throw new \InvalidArgumentException("Invalid layout revision.");
            }
            $revision = (int) $revision;
            if ($action === "visibility") {
                $visible = $_POST["visible"] ?? null;
                if (!in_array($visible, ["0", "1"], true)) {
                    throw new \InvalidArgumentException("Invalid visibility.");
                }
                $state = $service->visibility($_POST["key"] ?? null, $visible === "1", $revision);
            } elseif ($action === "layout") {
                $value = $_POST["layout"] ?? null;
                if (!is_string($value) || strlen($value) > 65536) {
                    throw new \InvalidArgumentException("Invalid layout payload.");
                }
                $layout = json_decode($value, true, 32, JSON_THROW_ON_ERROR);
                if (!is_array($layout)) {
                    throw new \InvalidArgumentException("Invalid layout payload.");
                }
                $state = $service->layout($layout, $revision);
            } else {
                $state = $service->reset($revision);
            }
            return $this->respond($state, $service->html($state));
        } catch (LayoutConflict $e) {
            return Response::make(409, $e->getMessage())
                ->data([])
                ->asJson()
                ->header("Cache-Control", "no-store");
        } catch (\InvalidArgumentException | \JsonException $e) {
            return Response::make(422, $e->getMessage())
                ->data([])
                ->asJson()
                ->header("Cache-Control", "no-store");
        }
    }
    private function respond(array $state, ?string $html = null): Response
    {
        if ($html !== null) {
            $state["html"] = $html;
        }
        return Response::make()->data($state)->asJson()->header("Cache-Control", "no-store");
    }
}
