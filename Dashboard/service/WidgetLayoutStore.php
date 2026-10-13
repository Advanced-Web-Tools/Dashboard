<?php
namespace Dashboard\service;
use database\interface\ITransactionalProvider;
use database\provider\DatabaseProvider;
final class WidgetLayoutStore
{
    public function __construct(private readonly ITransactionalProvider $provider = new DatabaseProvider()) {}
    public function load(int $adminId): array
    {
        if ($adminId < 1) {
            throw new \InvalidArgumentException("Invalid admin ID.");
        }
        $statement = $this->provider->execute(
            "SELECT payload, revision FROM dashboard_widget_layout WHERE admin_id = :admin_id",
            [":admin_id" => $adminId],
        );
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (!$row) {
            return ["revision" => 0, "order" => [], "visibility" => []];
        }
        $payload = json_decode($row["payload"], true, 32, JSON_THROW_ON_ERROR);
        if (
            !is_array($payload) ||
            !is_array($payload["order"] ?? null) ||
            !is_array($payload["visibility"] ?? null)
        ) {
            throw new \RuntimeException("Invalid stored widget layout.");
        }
        return [
            "revision" => (int) $row["revision"],
            "order" => $payload["order"],
            "visibility" => $payload["visibility"],
        ];
    }
    /** Compare-and-swap prevents a stale tab from overwriting another tab. */
    public function save(int $adminId, int $expected, array $layout): array
    {
        if ($expected < 0) {
            throw new \InvalidArgumentException("Invalid revision.");
        }
        return $this->provider->transaction(function () use ($adminId, $expected, $layout) {
            $current = $this->load($adminId);
            if ($current["revision"] !== $expected) {
                throw new LayoutConflict(
                    "Your dashboard changed in another tab. Reload the widget list and try again.",
                );
            }
            $revision = $expected + 1;
            $payload = json_encode(
                ["order" => $layout["order"], "visibility" => $layout["visibility"]],
                JSON_THROW_ON_ERROR,
            );
            $bindings = [
                ":admin_id" => $adminId,
                ":payload" => $payload,
                ":revision" => $revision,
                ":updated_at" => gmdate("Y-m-d H:i:s"),
            ];
            if ($expected === 0) {
                try {
                    $statement = $this->provider->execute(
                        "INSERT INTO dashboard_widget_layout (admin_id,payload,revision,updated_at) VALUES (:admin_id,:payload,:revision,:updated_at)",
                        $bindings,
                    );
                } catch (\Throwable $e) {
                    $duplicate = $e instanceof \PDOException && str_starts_with((string) $e->getCode(), "23");
                    $previous = $e->getPrevious();
                    if (
                        $previous instanceof \PDOException &&
                        str_starts_with((string) $previous->getCode(), "23")
                    ) {
                        $duplicate = true;
                    }
                    if ($duplicate) {
                        throw new LayoutConflict("Your dashboard changed in another tab.", 0, $e);
                    }
                    throw $e;
                }
            } else {
                $bindings[":expected"] = $expected;
                $statement = $this->provider->execute(
                    "UPDATE dashboard_widget_layout SET payload=:payload, revision=:revision, updated_at=:updated_at WHERE admin_id=:admin_id AND revision=:expected",
                    $bindings,
                );
                if ($statement->rowCount() !== 1) {
                    throw new LayoutConflict("Your dashboard changed in another tab.");
                }
            }
            $statement->closeCursor();
            return [
                "revision" => $revision,
                "order" => $layout["order"],
                "visibility" => $layout["visibility"],
            ];
        });
    }
}
